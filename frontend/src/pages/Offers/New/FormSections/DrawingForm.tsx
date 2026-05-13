import CardBox from "@components/CardBox";
import ClearIcon from "@mui/icons-material/Clear";
import CloudUploadIcon from "@mui/icons-material/CloudUpload";
import Grid from "@mui/material/Grid2";
import {
  Box,
  Button,
  CircularProgress,
  IconButton,
  InputAdornment,
  Skeleton,
  TextField,
  Typography,
} from "@mui/material";
import { Drawing } from "@interfaces/Drawing.model";
import { FunctionComponent, useEffect, useRef, useState } from "react";
import { OffersApi } from "@api/offers";
import { useApiErrorHandler } from "@hooks/useApiErrorHandler";
import { useDrawingEditable } from "@hooks/useDrawingEditable";
import { useOfferContext } from "@contexts/OfferProvider";
import { usePermissions } from "@hooks/usePermissions";

interface DrawingFormProps {}

const DrawingForm: FunctionComponent<DrawingFormProps> = () => {
  const { offerId, drawingFile, setDrawingFile } = useOfferContext();

  const { showError } = useApiErrorHandler();
  // Permissions
  const { canView, canEdit } = usePermissions();
  const { isDrawingEditable } = useDrawingEditable();
  const isViewable = canView("drawing");
  const isEditable = canEdit("drawing");

  const editable = isDrawingEditable();

  const [drawing, setDrawing] = useState<Drawing | null>(null);
  const [uploading, setUploading] = useState(false);
  const [loadingDrawing, setLoadingDrawing] = useState(false);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [savedBlobUrl, setSavedBlobUrl] = useState<string | null>(null);

  const fileInputRef = useRef<HTMLInputElement>(null);

  const fetchDrawing = async () => {
    try {
      setLoadingDrawing(true);
      const res = await OffersApi.getDrawing(offerId!);
      setDrawing(res);
      if (res) {
        if (savedBlobUrl) URL.revokeObjectURL(savedBlobUrl);
        const blobUrl = await OffersApi.getDrawingBlobUrl(offerId!);
        setSavedBlobUrl(blobUrl);
      }
    } catch (error: any) {
      console.error(error);
    } finally {
      setLoadingDrawing(false);
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setDrawingFile(file);
      setPreviewUrl(URL.createObjectURL(file)); // show preview
    }
  };

  const handleUpload = async () => {
    if (!drawingFile) return;
    setUploading(true);
    try {
      await OffersApi.storeDrawing(offerId!, drawingFile);
      setDrawingFile(null);
      setPreviewUrl(null); // reset after upload
      await fetchDrawing(); // fetch the saved one
    } catch (err) {
      showError(err);
    }
    setUploading(false);
  };

  useEffect(() => {
    if (!offerId) return;
    fetchDrawing();
  }, [offerId]);

  if (!isViewable) return null;

  return (
    <CardBox>
      <Grid container spacing={2}>
        {/* Left Column – Drawing Info */}
        <Grid size={{ xs: 12, md: 6 }}>
          <Typography variant="body1">
            <strong>Dateiname:</strong>{" "}
            {drawing?.filename || <em>Kein Dokument vorhanden</em>}
          </Typography>
          <Typography variant="body1" pt={2}>
            <strong>Stand Zeichnung:</strong>{" "}
            {drawing?.upload_date ? (
              new Date(drawing.upload_date).toLocaleDateString("de-DE")
            ) : (
              <em>-</em>
            )}
          </Typography>
        </Grid>

        {/* Right Column – Upload Area */}
        <Grid size={{ xs: 12, md: 6 }}>
          <Box display="flex" alignItems="center" justifyContent="end" gap={2}>
            {/* Hidden file input */}
            <input
              type="file"
              accept="application/pdf"
              ref={fileInputRef}
              style={{ display: "none" }}
              onChange={handleFileChange}
            />

            {/* TextField styled like Figma */}
            <TextField
              disabled={!drawingFile || uploading || !editable}
              variant="outlined"
              size="small"
              label="neue Datei"
              value={drawingFile?.name || ""}
              placeholder="PDF auswählen"
              InputProps={{
                readOnly: true,
                sx: {
                  width: "400px",
                  borderRadius: 1,
                  borderBottom: "1px solid #ccc",
                  backgroundColor: "#f5f5f5",
                  "& .MuiOutlinedInput-notchedOutline": {
                    border: "none",
                  },
                },
                startAdornment: editable && drawingFile && (
                  <InputAdornment position="start">
                    <IconButton
                      onClick={() => {
                        setDrawingFile(null);
                        setPreviewUrl(null);
                      }}
                      edge="start"
                      size="small"
                      aria-label="Datei entfernen"
                    >
                      <ClearIcon fontSize="small" />
                    </IconButton>
                  </InputAdornment>
                ),
                endAdornment: editable && isEditable && (
                  <InputAdornment position="end">
                    <IconButton
                      onClick={() => fileInputRef.current?.click()}
                      edge="end"
                      size="small"
                      aria-label="Datei auswählen"
                    >
                      <CloudUploadIcon />
                    </IconButton>
                  </InputAdornment>
                ),
              }}
            />

            {/* Upload Button */}
            <Button
              variant="contained"
              onClick={handleUpload}
              disabled={!drawingFile || uploading || !editable}
              startIcon={uploading ? <CircularProgress size={16} color="inherit" /> : undefined}
            >
              {uploading ? "Hochladen..." : "Hochladen"}
            </Button>
          </Box>
        </Grid>
      </Grid>

      {/* PDF Preview */}
      {previewUrl && !uploading ? (
        <Box mt={3}>
          <Typography variant="subtitle1" mb={1}>
            Vorschau der ausgewählten Datei:
          </Typography>
          <iframe
            src={previewUrl}
            width="100%"
            height="600px"
            style={{ border: "1px solid #ccc" }}
          />
        </Box>
      ) : (savedBlobUrl || uploading || loadingDrawing) ? (
        <Box mt={3}>
          <Typography variant="subtitle1" mb={1}>
            Gespeicherte Zeichnung:
          </Typography>
          <Box position="relative" width="100%" height="600px">
            {(uploading || loadingDrawing) && (
              <Box
                sx={{
                  position: "absolute",
                  inset: 0,
                  display: "flex",
                  flexDirection: "column",
                  alignItems: "center",
                  justifyContent: "center",
                  gap: 2,
                  bgcolor: "rgba(255,255,255,0.85)",
                  zIndex: 1,
                  borderRadius: 1,
                }}
              >
                <CircularProgress size={36} />
                <Typography variant="body2" color="text.secondary">
                  {uploading ? "Datei wird hochgeladen..." : "Zeichnung wird geladen..."}
                </Typography>
              </Box>
            )}
            {savedBlobUrl && !uploading && !loadingDrawing ? (
              <iframe
                src={savedBlobUrl}
                width="100%"
                height="600px"
                style={{ border: "1px solid #ccc", display: "block" }}
              />
            ) : (
              <Skeleton variant="rectangular" width="100%" height="600px" sx={{ borderRadius: 1 }} />
            )}
          </Box>
        </Box>
      ) : null}
    </CardBox>
  );
};

export default DrawingForm;
