export type Column<T> = {
  label: string;
  render: (row: T) => React.ReactNode;
  sortKey?: keyof T;
};

export type Props<T> = {
  data: T[];
  columns: Column<T>[];
  rowsPerPageOptions?: number[];
  actions?: (row: T) => React.ReactNode;
  searchableField?: keyof T;
  loading?: boolean;
  onSortChange?: (sortBy: string, sortDir: "asc" | "desc") => void;
  sortBy?: string;
  sortDir?: "asc" | "desc";
  onSearchChange?: (term: string) => void;
  /** When this value changes (e.g. sort/search from server), table page resets to 0 */
  paginationResetKey?: string;
};
