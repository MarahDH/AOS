import { FunctionComponent, useEffect, useState, useRef } from "react";
import CardBox from "../../../components/CardBox";
import BasicDataTab from "./Tabs/BasicData";
import CalculationTab from "./Tabs/Calculation";
import PricesTab from "./Tabs/Prices";
import DrawingTab from "./Tabs/Drawing";
import ProcessSheetTab from "./Tabs/ProcessSheet";
import { Box, CircularProgress, LinearProgress, Tab, Tabs } from "@mui/material";
import { FormikProvider, useFormik } from "formik";
import { initialValues } from "./Index";
import { useParams } from "react-router-dom";
import { useOfferContext } from "@contexts/OfferProvider";
import { OffersApi } from "@api/offers";
import { usePermissions } from "@hooks/usePermissions";
import { useQuery, useQueryClient } from "@tanstack/react-query";

type OfferFormProps = object;

const allTabs = [
  {
    label: "Grunddaten",
    component: <BasicDataTab />,
    permission: { action: "view", subject: "basic_data" },
  },
  {
    label: "Kalkulation",
    component: <CalculationTab />,
    permission: { action: "view", subject: "calculation" },
  },
  {
    label: "Preise",
    component: <PricesTab />,
    permission: { action: "view", subject: "prices" },
  },
  {
    label: "Zeichnung",
    component: <DrawingTab />,
    permission: { action: "view", subject: "drawing" },
  },
  {
    label: "Laufkarte",
    component: <ProcessSheetTab />,
    permission: { action: "view", subject: "process_sheet" },
  },
];

const OfferForm: FunctionComponent<OfferFormProps> = () => {
  const { id } = useParams();
  const {
    setOfferId,
    setOfferData,
    resetOffer,
    setIsLoadingOfferDetails,
  } = useOfferContext();
  const { canView } = usePermissions();
  const queryClient = useQueryClient();

  const numericId = id ? Number(id) : undefined;
  const tabs = allTabs.filter((tab) => canView(tab.permission.subject));

  const [selectedTab, setSelectedTab] = useState(() => {
    const saved = localStorage.getItem("offer_form_selected_tab");
    return saved !== null ? Number(saved) : 0;
  });
  const [visitedTabs, setVisitedTabs] = useState<Set<number>>(
    () =>
      new Set([
        localStorage.getItem("offer_form_selected_tab") !== null
          ? Number(localStorage.getItem("offer_form_selected_tab"))
          : 0,
      ])
  );
  const previousTabRef = useRef<number>(selectedTab);

  // Single source of truth for the offer object — shared with useSaveFieldMutation via cache key ["offer", id]
  const { data: offerQueryData, isLoading: isOfferLoading, isFetching: isOfferFetching } = useQuery({
    queryKey: ["offer", numericId],
    queryFn: () => OffersApi.getOfferById(numericId!),
    enabled: !!numericId,
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
  });

  // Background refetch indicator — shown as a thin progress bar, no full spinner
  const isSilentRefreshing = isOfferFetching && !isOfferLoading;

  const formik = useFormik({
    initialValues,
    onSubmit: () => {},
  });

  // Sync React Query data into OfferProvider so all child components stay consistent
  useEffect(() => {
    if (!numericId) {
      resetOffer();
      setIsLoadingOfferDetails(false);
      return;
    }
    if (offerQueryData) {
      setOfferId(offerQueryData.id);
      setOfferData(offerQueryData);
      setIsLoadingOfferDetails(false);
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [offerQueryData, numericId]);

  // Clean up persisted tab selection when leaving the form
  useEffect(() => {
    return () => localStorage.removeItem("offer_form_selected_tab");
  }, [id]);

  // When switching from Calculation → Prices, the backend has already recalculated
  // graduated pricing values. Invalidate both cache keys so the Prices tab shows fresh data.
  useEffect(() => {
    if (!numericId || selectedTab === previousTabRef.current) return;

    const pricesTabIndex = tabs.findIndex((tab) => tab.label === "Preise");
    const calculationTabIndex = tabs.findIndex((tab) => tab.label === "Kalkulation");

    if (
      pricesTabIndex !== -1 &&
      selectedTab === pricesTabIndex &&
      previousTabRef.current === calculationTabIndex
    ) {
      queryClient.invalidateQueries({ queryKey: ["offer", numericId] });
      queryClient.invalidateQueries({ queryKey: ["offer-data", numericId] });
    }

    previousTabRef.current = selectedTab;
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedTab, numericId]);

  if (!!numericId && isOfferLoading) {
    return (
      <Box
        display="flex"
        justifyContent="center"
        alignItems="center"
        minHeight="50vh"
      >
        <CircularProgress />
      </Box>
    );
  }

  return (
    <FormikProvider value={formik}>
      <CardBox label="Aufträge und Angebote" margin="20px">
        {isSilentRefreshing && <LinearProgress sx={{ mb: 1 }} />}
        <Box>
          <Tabs
            variant="fullWidth"
            value={selectedTab}
            onChange={(_, newValue) => {
              setSelectedTab(newValue);
              setVisitedTabs((prev) => new Set([...prev, newValue]));
              localStorage.setItem(
                "offer_form_selected_tab",
                newValue.toString()
              );
            }}
          >
            {tabs.map((tab, index) => (
              <Tab key={index} label={tab.label} />
            ))}
          </Tabs>

          <Box mt={2}>
            {tabs.map((tab, index) => (
              <Box key={index} hidden={selectedTab !== index}>
                {visitedTabs.has(index) ? tab.component : null}
              </Box>
            ))}
          </Box>
        </Box>
      </CardBox>
    </FormikProvider>
  );
};

export default OfferForm;
