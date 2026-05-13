import {
  Table,
  TableHead,
  TableBody,
  TableCell,
  TableRow,
  TablePagination,
  TableContainer,
  Paper,
  TextField,
  Box,
  InputAdornment,
  CircularProgress,
  TableSortLabel,
  Tooltip,
  Typography,
} from "@mui/material";
import { GridSearchIcon } from "@mui/x-data-grid";
import { useEffect, useState } from "react";
import { Props } from "../shared/models/types/Table";

export function MTable<T>({
  data,
  columns,
  rowsPerPageOptions = [5, 10, 14, 20],
  actions,
  searchableField,
  loading = false,
  onSortChange,
  sortBy: controlledSortBy,
  sortDir: controlledSortDir,
  onSearchChange,
  paginationResetKey,
}: Props<T>) {
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(rowsPerPageOptions[0]);
  const [search, setSearch] = useState("");
  const fireSearch = (term: string) => onSearchChange?.(term);
  const [localSortKey, setLocalSortKey] = useState<keyof T | null>(null);
  const [localSortDir, setLocalSortDir] = useState<"asc" | "desc">("asc");

  const isControlled = !!onSortChange;
  const activeSortKey = isControlled ? controlledSortBy : localSortKey ? String(localSortKey) : undefined;
  const activeSortDir = isControlled ? (controlledSortDir ?? "asc") : localSortDir;

  useEffect(() => {
    if (paginationResetKey !== undefined) {
      setPage(0);
    }
  }, [paginationResetKey]);

  const handleSortClick = (key: keyof T) => {
    const keyStr = String(key);
    if (isControlled) {
      const newDir = activeSortKey === keyStr && activeSortDir === "asc" ? "desc" : "asc";
      onSortChange!(keyStr, newDir);
    } else {
      if (localSortKey === key) {
        setLocalSortDir((prev) => (prev === "asc" ? "desc" : "asc"));
      } else {
        setLocalSortKey(key);
        setLocalSortDir("asc");
      }
    }
    setPage(0);
  };

  const handleChangePage = (_: any, newPage: number) => setPage(newPage);
  const handleChangeRowsPerPage = (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  };

  const filteredData = !onSearchChange && searchableField
    ? data.filter((row) =>
        String(row[searchableField])
          .toLowerCase()
          .includes(search.toLowerCase())
      )
    : data;

  const sortedData = !isControlled && localSortKey
    ? [...filteredData].sort((a, b) => {
        const av = String(a[localSortKey] ?? "");
        const bv = String(b[localSortKey] ?? "");
        const cmp = av.localeCompare(bv, undefined, { numeric: true, sensitivity: "base" });
        return localSortDir === "asc" ? cmp : -cmp;
      })
    : filteredData;

  const paginated = sortedData.slice(
    page * rowsPerPage,
    page * rowsPerPage + rowsPerPage
  );

  return (
    <Box p={0}>
      <Box mb={2} display="flex" justifyContent="flex-end">
        <Tooltip
          title={onSearchChange ? "Enter drücken oder Feld verlassen, um zu suchen" : ""}
          placement="left"
          arrow
        >
        <TextField
          size="small"
          placeholder="Suchen"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(0);
          }}
          onBlur={() => fireSearch(search)}
          onKeyDown={(e) => {
            if (e.key === "Enter") fireSearch(search);
          }}
          slotProps={{
            input: {
              endAdornment: (
                <InputAdornment position="end">
                  {onSearchChange && (
                    <Typography
                      sx={{
                        fontSize: "11px",
                        color: "text.disabled",
                        border: "1px solid",
                        borderColor: "divider",
                        borderRadius: "4px",
                        px: 0.75,
                        py: 0.1,
                        mr: 0.75,
                        lineHeight: "18px",
                        userSelect: "none",
                      }}
                    >
                      ↵
                    </Typography>
                  )}
                  <GridSearchIcon color="action" />
                </InputAdornment>
              ),
            },
          }}
        />
        </Tooltip>
      </Box>

      <Box p={0} minHeight={0}>
        <TableContainer
          component={Paper}
          sx={{
            flexGrow: 1,
            overflowY: "auto",
          }}
        >
          <Table stickyHeader>
            <TableHead>
              <TableRow>
                {columns.map((col, i) => {
                  const colKey = col.sortKey ? String(col.sortKey) : undefined;
                  const isActive = !!colKey && activeSortKey === colKey;
                  return (
                    <TableCell key={i} sortDirection={isActive ? activeSortDir : false}>
                      {col.sortKey ? (
                        <TableSortLabel
                          active={isActive}
                          direction={isActive ? activeSortDir : "asc"}
                          onClick={() => handleSortClick(col.sortKey!)}
                        >
                          {col.label}
                        </TableSortLabel>
                      ) : (
                        col.label
                      )}
                    </TableCell>
                  );
                })}
                {actions && <TableCell align="right"></TableCell>}
              </TableRow>
            </TableHead>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell
                    colSpan={columns.length + (actions ? 1 : 0)}
                    align="center"
                  >
                    <Box py={3}>
                      <CircularProgress size={28} />
                    </Box>
                  </TableCell>
                </TableRow>
              ) : paginated.length === 0 ? (
                <TableRow>
                  <TableCell
                    colSpan={columns.length + (actions ? 1 : 0)}
                    align="center"
                  >
                    Keine Daten vorhanden
                  </TableCell>
                </TableRow>
              ) : (
                paginated.map((row, i) => (
                  <TableRow key={i}>
                    {columns.map((col, j) => (
                      <TableCell key={j}>{col.render(row)}</TableCell>
                    ))}
                    {actions && (
                      <TableCell align="right">{actions(row)}</TableCell>
                    )}
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </TableContainer>

        <TablePagination
          component="div"
          count={sortedData.length}
          page={page}
          onPageChange={handleChangePage}
          rowsPerPage={rowsPerPage}
          rowsPerPageOptions={rowsPerPageOptions}
          onRowsPerPageChange={handleChangeRowsPerPage}
        />
      </Box>
    </Box>
  );
}
