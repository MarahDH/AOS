import AddIcon from "@mui/icons-material/Add";
import CardBox from "../../components/CardBox";
import IconAction from "@components/IconAction";
import RoundedIconButton from "@components/RoundedIconButton";
import { Box } from "@mui/material";
import {
  ContentCopy,
  Delete,
  Edit,
  InsertDriveFileRounded,
} from "@mui/icons-material";
import { FunctionComponent, useState } from "react";
import { MTable } from "@components/MTable";
import { OfferColumns } from "./Columns";
import { OffersApi } from "@api/offers";
import { OffersModel } from "@interfaces/Offers.model";
import { useApiSuccessHandler } from "@hooks/useApiSuccessHandler";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";
import { useOfferContext } from "@contexts/OfferProvider";
import { usePermissions } from "@hooks/usePermissions";
import ConfirmationDialog from "@components/ConfirmationDialog";
import TemplateDialog from "./TemplateDialog";

type OffersPageProps = object;

const OffersPage: FunctionComponent<OffersPageProps> = () => {
  const navigate = useNavigate();
  const { showSuccess } = useApiSuccessHandler();
  const { resetOffer } = useOfferContext();
  const { canEdit, canCreate, canDuplicate, canExport, canDelete } = usePermissions();
  const queryClient = useQueryClient();

  const [exportingOfferId, setExportingOfferId] = useState<number | null>(null);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [selectedOffer, setSelectedOffer] = useState<OffersModel | null>(null);
  const [templateDialogOpen, setTemplateDialogOpen] = useState(false);
  const [templates, setTemplates] = useState<string[]>([]);
  const [exportOfferId, setExportOfferId] = useState<number | null>(null);
  const [sortBy, setSortBy] = useState("general_offer_number");
  const [sortDir, setSortDir] = useState<"asc" | "desc">("asc");
  const [searchTerm, setSearchTerm] = useState("");

  // Cached list — navigating away and back reuses cached data
  const { data: offers = [], isLoading } = useQuery({
    queryKey: ["offers", sortBy, sortDir, searchTerm],
    queryFn: () => OffersApi.getAllOffers(sortBy, sortDir, searchTerm),
    staleTime: 60_000,
    // Keep previous results visible while re-fetching for sort/search changes
    placeholderData: (prev) => prev,
  });

  const duplicateMutation = useMutation({
    mutationFn: (offerId: number) => OffersApi.duplicateOffer(offerId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["offers"] }),
  });

  const deleteMutation = useMutation({
    mutationFn: (offerId: number) => OffersApi.deleteOffer(offerId),
    onSuccess: () => {
      showSuccess("Angebot erfolgreich gelöscht");
      queryClient.invalidateQueries({ queryKey: ["offers"] });
      setDeleteDialogOpen(false);
      setSelectedOffer(null);
    },
  });

  const openTemplateDialog = async (offerId: number) => {
    setExportOfferId(offerId);
    setTemplateDialogOpen(true);
    const files = await OffersApi.getTemplates();
    setTemplates(files);
  };

  const handleTemplateSelect = async (filename: string) => {
    setTemplateDialogOpen(false);
    if (exportOfferId) {
      setExportingOfferId(exportOfferId);
      try {
        await OffersApi.export(exportOfferId, filename);
      } catch {
        // export() already shows error via axiosInstance interceptor
      } finally {
        setExportingOfferId(null);
        setExportOfferId(null);
      }
    }
  };

  const handleAdd = () => {
    resetOffer();
    navigate("/angebote/neu");
  };

  const handleSortChange = (by: string, dir: "asc" | "desc") => {
    setSortBy(by);
    setSortDir(dir);
  };

  const handleSearchChange = (term: string) => {
    setSearchTerm(term);
  };

  const handleDelete = (offer: OffersModel) => {
    setSelectedOffer(offer);
    setDeleteDialogOpen(true);
  };

  const confirmDelete = () => {
    if (selectedOffer?.id) {
      deleteMutation.mutate(selectedOffer.id);
    }
  };

  return (
    <>
      <Box p={2}>
        <CardBox label="Aufträge und Angebote">
          <MTable
            data={offers}
            columns={OfferColumns}
            loading={isLoading}
            onSortChange={handleSortChange}
            sortBy={sortBy}
            sortDir={sortDir}
            onSearchChange={handleSearchChange}
            paginationResetKey={`${sortBy}-${sortDir}-${searchTerm}`}
            actions={(row) => (
              <>
                {canExport("offer") && (
                  <IconAction
                    tooltip="Mit Vorlage exportieren"
                    onClick={() => openTemplateDialog(row.id)}
                    disabled={exportingOfferId === row.id}
                  >
                    <InsertDriveFileRounded fontSize="small" />
                  </IconAction>
                )}
                {canDuplicate("offer") && (
                  <IconAction
                    tooltip="Angebot duplizieren"
                    onClick={() => duplicateMutation.mutate(row.id)}
                    disabled={duplicateMutation.isPending}
                  >
                    <ContentCopy fontSize="small" />
                  </IconAction>
                )}
                {canEdit("offer") && (
                  <IconAction
                    tooltip="Bearbeiten"
                    onClick={() => navigate(`/angebote/${row.id}`)}
                  >
                    <Edit fontSize="small" />
                  </IconAction>
                )}
                {canDelete("offer") && (
                  <IconAction
                    tooltip="Löschen"
                    onClick={() => handleDelete(row)}
                  >
                    <Delete fontSize="small" />
                  </IconAction>
                )}
              </>
            )}
          />

          {canCreate("offer") && (
            <RoundedIconButton
              icon={<AddIcon fontSize="small" />}
              label="NEU"
              onClick={handleAdd}
            />
          )}

          <ConfirmationDialog
            open={deleteDialogOpen}
            onClose={() => setDeleteDialogOpen(false)}
            onConfirm={confirmDelete}
            title="Angebot löschen"
            message="Möchten Sie dieses Angebot wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden."
          />

          <TemplateDialog
            open={templateDialogOpen}
            templates={templates}
            onClose={() => setTemplateDialogOpen(false)}
            onSelect={handleTemplateSelect}
          />
        </CardBox>
      </Box>
    </>
  );
};

export default OffersPage;
