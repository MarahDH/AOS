import { FunctionComponent, useState } from "react";
import { useField } from "formik";
import { FormControl, TextField, TextFieldProps, InputAdornment, IconButton } from "@mui/material";
import { Visibility, VisibilityOff } from "@mui/icons-material";
import { formatNumberToGerman } from "@utils/formatNumbers";

interface FormInputFieldProps extends Omit<TextFieldProps, "name" | "variant"> {
  name: string;
  required?: boolean;
  numeric?: boolean;
  hiddenLabel?: boolean;
}

const FormInputField: FunctionComponent<FormInputFieldProps> = ({
  name,
  type = "text",
  required = false,
  disabled = false,
  value,
  onChange,
  numeric = false,
  hiddenLabel = false,
  ...props
}) => {
  const [field, meta] = useField(name);
  const [showPassword, setShowPassword] = useState(false);

  const configTextField: TextFieldProps = {
    ...field,
    ...props,
    fullWidth: true,
    type: type === "password" && showPassword ? "text" : type,
    required,
    disabled,
    onChange: onChange || field.onChange,
    error: !!meta.touched && !!meta.error,
    helperText: meta.touched && meta.error ? meta.error : undefined,
    value: value ?? (numeric ? formatNumberToGerman(field.value) : field.value),
    variant: "filled",
    hiddenLabel: hiddenLabel,
  };

  const handleTogglePasswordVisibility = () => {
    setShowPassword(!showPassword);
  };

  return (
    <FormControl fullWidth variant="outlined">
      <TextField
        {...configTextField}
        InputProps={{
          endAdornment: type === "password" ? (
            <InputAdornment position="end">
              <IconButton
                aria-label="toggle password visibility"
                onClick={handleTogglePasswordVisibility}
                edge="end"
                size="small"
              >
                {showPassword ? <VisibilityOff /> : <Visibility />}
              </IconButton>
            </InputAdornment>
          ) : undefined,
        }}
        sx={{
          ".MuiInputBase-input": {
            padding: hiddenLabel ? "5px" : null,
          },
        }}
      />
    </FormControl>
  );
};

export default FormInputField;
