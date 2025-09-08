import FormInputField from "@components/FormInputField";
import FormSelectField from "@components/FormSelectField";
import { Box, CircularProgress } from "@mui/material";
import { FormikProps } from "formik";
import { UserFormValues } from "./UserFormModal";
import { FormMode } from "@enums/FormMode";
import { useEffect, useState } from "react";
import { RolesApi } from "@api/roles";

interface Props {
  formik: FormikProps<UserFormValues>;
  mode: FormMode;
}

const roleLabelMap: Record<string, string> = {
  admin: "Administrator",
  sales: "Vertrieb",
  production: "Produktion",
};

const UserForm = ({ formik, mode }: Props) => {
  const { values, handleChange } = formik;
  const [roles, setRoles] = useState<any[]>([]);
  const [loadingRoles, setLoadingRoles] = useState(true);

  const fetchRoles = async () => {
    try {
      setLoadingRoles(true);
      const data = await RolesApi.getAllRoles();
      const mapped = data.map((role) => ({
        label: roleLabelMap[role.name] || role.name,
        value: role.id,
      }));

      setRoles(mapped);
    } catch (error) {
      console.error("Fehler beim Laden der Rollen", error);
    } finally {
      setLoadingRoles(false);
    }
  };

  useEffect(() => {
    fetchRoles();
  }, []);

  useEffect(() => {
    if (roles.length && !roles.some((r) => r.value === values.role_id)) {
      // Reset to first available role if current role_id is not found
      formik.setFieldValue("role_id", roles[0].value);
    }
  }, [roles]);

  if (loadingRoles) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" py={4}>
        <CircularProgress size={32} />
      </Box>
    );
  }

  return (
    <Box display="flex" flexDirection="column" gap={2} mt={1}>
      <FormInputField
        name="name"
        label="Benutzername"
        required
        value={values.name}
        onChange={handleChange}
      />
      <FormInputField
        name="email"
        label="E-Mail"
        type="email"
        required
        value={values.email}
        onChange={handleChange}
      />
      {mode === FormMode.CREATE && (
        <>
          <FormInputField
            name="password"
            label="Passwort"
            type="password"
            required
            value={values.password}
            onChange={handleChange}
          />
          <FormInputField
            name="confirmPassword"
            label="Passwort bestätigen"
            type="password"
            required
            value={values.confirmPassword}
            onChange={handleChange}
          />
        </>
      )}

      <FormSelectField
        name="role_id"
        label="Rolle"
        required
        onChange={handleChange}
        options={roles}
        disabled={loadingRoles}
      />
    </Box>
  );
};

export default UserForm;
