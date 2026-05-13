import { useCallback, useEffect, useRef } from "react";
import { useFormikContext } from "formik";
import axios from "axios";
import debounce from "lodash/debounce";

const FIELDS_THAT_TRIGGER_CALCULATION = [
  "raw_material_price",
  // Add more trigger fields
];

export const useCalculatedValues = () => {
  const { values, setFieldValue } = useFormikContext<any>();
  const previousValues = useRef<any>({});

  // Keep setFieldValue in a ref so the debounced callback never captures a stale version
  const setFieldValueRef = useRef(setFieldValue);
  useEffect(() => {
    setFieldValueRef.current = setFieldValue;
  });

  // Stable debounced function — created once per mount, cancelled on unmount
  const triggerCalculation = useCallback(
    debounce(async (payload: Record<string, unknown>) => {
      try {
        const response = await axios.post("/api/offer/calculate", payload);
        const calculated = response.data;
        setFieldValueRef.current("total_price", calculated.total_price);
        setFieldValueRef.current("total_weight", calculated.total_weight);
      } catch (err) {
        console.error("Calculation error:", err);
      }
    }, 800),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [] // intentionally empty — we access setFieldValue through the ref
  );

  useEffect(() => {
    return () => triggerCalculation.cancel();
  }, [triggerCalculation]);

  useEffect(() => {
    const changed = FIELDS_THAT_TRIGGER_CALCULATION.some(
      (field) => values[field] !== previousValues.current[field]
    );

    if (changed) {
      const payload = FIELDS_THAT_TRIGGER_CALCULATION.reduce(
        (acc, key) => {
          acc[key] = values[key];
          return acc;
        },
        {} as Record<string, unknown>
      );

      triggerCalculation(payload);
      previousValues.current = { ...values };
    }
  }, [values, triggerCalculation]);
};
