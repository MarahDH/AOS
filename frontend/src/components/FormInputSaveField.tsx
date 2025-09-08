import React, { FunctionComponent, useEffect, useState } from "react";
import { useField, useFormikContext } from "formik";
import {
  FormControl,
  InputAdornment,
  TextField,
  TextFieldProps,
} from "@mui/material";
import CheckCircleIcon from "@mui/icons-material/CheckCircle";
import { useSaveFieldMutation } from "@hooks/useSaveFieldMutation";
import { parseGermanNumber, formatNumberToGerman, formatIntegerToGerman } from "@utils/formatNumbers";
import { useNavigate } from "react-router-dom";

interface FormInputFieldProps extends Omit<TextFieldProps, "name"> {
  name: string;
  required?: boolean;
  numeric?: boolean;
  integerOnly?: boolean;
  onSaved?: () => void;
  hiddenLabel?: boolean;
  variant?: "standard" | "outlined" | "filled";
  alignText?: string;
}

const FormInputSaveField: FunctionComponent<FormInputFieldProps> = ({
  name,
  type = "text",
  required = false,
  disabled = false,
  numeric = false,
  integerOnly = false,
  hiddenLabel = false,
  variant = "filled",
  alignText,
  onSaved,
  ...props
}) => {
  const [field, meta] = useField(name);
  const { setFieldValue } = useFormikContext();
  const [inputValue, setInputValue] = useState(field.value || "");
  const [justSaved, setJustSaved] = useState(false);
  const navigate = useNavigate();
  const mutation = useSaveFieldMutation(navigate);


  // 🔄 Format value when loading (but not while typing)
  useEffect(() => {
    if (numeric && typeof field.value === "number") {
      if (integerOnly) {
        // Format integers with thousand separators (e.g., 1000 -> 1.000)
        setInputValue(formatIntegerToGerman(field.value));
      } else {
        setInputValue(formatNumberToGerman(field.value));
      }
    } else if (!numeric) {
      setInputValue(field.value || "");
    }
  }, [field.value, numeric, integerOnly]);

  // 🚫 Prevent auto-formatting while user is typing
  const [isTyping, setIsTyping] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    let val = e.target.value;

    if (numeric && integerOnly) {
      // Allow dots for thousand separators in integer fields
      val = val.replace(/[^0-9.]/g, "");
      // Ensure only one dot is allowed and it's followed by exactly 3 digits
      const parts = val.split(".");
      if (parts.length > 1) {
        // Keep only the first part and parts that are exactly 3 digits
        const filteredParts = parts.filter((part, index) => index === 0 || part.length === 3);
        val = filteredParts.join(".");
      }
    } else if (numeric) {
      val = val.replace(/[^0-9,]/g, "");
      const parts = val.split(",");
      if (parts.length > 2) {
        val = parts[0] + "," + parts[1]; // keep only one comma
      }
    }

    setInputValue(val);
    setIsTyping(true); // Mark that user is actively typing
  };

  const handleBlur = () => {
    setIsTyping(false); // User finished editing
    saveField(inputValue); // Save on blur
  };

  const saveField = (value: string) => {
    let valueToSave: string | number = value;

    if (numeric) {
      const parsed = parseGermanNumber(value);
      if (parsed === null) {
        setFieldValue(name, value); // fallback
        return;
      }
      valueToSave = parsed;
    }

    const normalizedCurrentValue = numeric
      ? parseGermanNumber(field.value?.toString() || "")
      : field.value;

    if (valueToSave === normalizedCurrentValue) {
      return; // no change
    }

    setFieldValue(name, valueToSave);

    mutation.mutate(
      { name, value: valueToSave },
      {
        onSuccess: () => {
          setJustSaved(true);
          setTimeout(() => setJustSaved(false), 2000);
          onSaved?.();
        },
      }
    );

    // Apply formatting after save (only if user is not typing)
    if (!isTyping && numeric && typeof valueToSave === "number") {
      if (integerOnly) {
        // Format integers with thousand separators (e.g., 1000 -> 1.000)
        setInputValue(formatIntegerToGerman(valueToSave));
      } else {
        setInputValue(formatNumberToGerman(valueToSave));
      }
    }
  };

  return (
    <FormControl fullWidth variant="outlined">
      <TextField
        {...props}
        value={inputValue}
        onChange={handleChange}
        onBlur={handleBlur}
        fullWidth
        type="text" // always text to control custom formatting
        required={required}
        hiddenLabel={hiddenLabel}
        disabled={disabled}
        error={!!meta.touched && !!meta.error}
        helperText={meta.touched && meta.error ? meta.error : undefined}
        variant={variant}
        InputProps={{
          endAdornment: justSaved ? (
            <InputAdornment position="end">
              <CheckCircleIcon sx={{ color: "green" }} fontSize="small" />
            </InputAdornment>
          ) : undefined,
        }}
        sx={{
          "& .MuiInputLabel-root.Mui-disabled": {
            color: "black",
          },
          ".MuiInputBase-input": {
            padding: hiddenLabel ? "5px" : null,
            textAlign: alignText ? alignText : null,
          },
        }}
      />
    </FormControl>
  );
};

export default FormInputSaveField;
