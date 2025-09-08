import * as Yup from "yup";
import BaseDialog from "@components/BaseDialog";
import UserForm from "./UserForm";
import { Box, Button } from "@mui/material";
import { Form, Formik } from "formik";
import { FormMode } from "@enums/FormMode";
import { UsersApi } from "@api/users";
import { enqueueSnackbar } from "notistack";

export interface UserFormValues {
  id?: number;
  name: string;
  password: string;
  confirmPassword?: string;
  email: string;
  role_id: number;
}

interface Props {
  open: boolean;
  onClose: () => void;
  initialValues?: UserFormValues;
  mode?: FormMode;
  onSuccess: () => void;
}

const UserFormModal = ({
  open,
  onClose,
  initialValues = {
    name: "",
    email: "",
    password: "",
    confirmPassword: "",
    role_id: 1,
  },
  mode = FormMode.CREATE,
  onSuccess,
}: Props) => {
  const getValidationSchema = (mode: FormMode) =>
    Yup.object({
      name: Yup.string().required("Benutzername ist erforderlich"),
      email: Yup.string()
        .email("Ungültige E-Mail")
        .required("E-Mail ist erforderlich"),
      role_id: Yup.number()
        .typeError("Rolle ist erforderlich")
        .required("Rolle ist erforderlich"),
      ...(mode === FormMode.CREATE && {
        password: Yup.string()
          .min(6, "Passwort muss mindestens 6 Zeichen lang sein")
          .required("Passwort ist erforderlich"),
        confirmPassword: Yup.string()
          .oneOf([Yup.ref("password")], "Passwörter stimmen nicht überein")
          .required("Passwort-Bestätigung ist erforderlich")
          .nullable(),
      }),
    });

  const handleFormSubmit = async (values: UserFormValues, actions: any) => {
    try {
      if (mode === FormMode.EDIT && values.id != null) {
        const { password, confirmPassword, ...rest } = values;
        const updateData = password ? { ...rest, password } : rest;
        await UsersApi.updateUser(values.id, updateData);
        enqueueSnackbar("Benutzer erfolgreich aktualisiert", {
          variant: "success",
        });
      } else {
        const { confirmPassword, ...createData } = values;
        await UsersApi.createUser(createData);
        enqueueSnackbar("Benutzer erfolgreich erstellt", {
          variant: "success",
        });
      }

      actions.resetForm();
      actions.setSubmitting(false);
      onSuccess();
      onClose();
    } catch (error) {
      actions.setSubmitting(false);
    }
  };

  return (
    <Formik
      enableReinitialize
      initialValues={initialValues}
      validationSchema={getValidationSchema(mode)}
      onSubmit={handleFormSubmit}
    >
      {(formik) => (
        <Form>
          <BaseDialog
            title={
              mode === FormMode.EDIT
                ? "Benutzer bearbeiten"
                : "Neuen Benutzer hinzufügen"
            }
            open={open}
            onClose={onClose}
            hideActions={true}
            onSubmit={formik.submitForm}
          >
            <UserForm formik={formik} mode={mode} />
            <Box mt={2} display="flex" justifyContent="flex-end" gap={1}>
              <Button
                onClick={() => {
                  formik.resetForm();
                  onClose();
                }}
              >
                Abbrechen
              </Button>
              <Button
                type="submit"
                onClick={formik.submitForm}
                variant="contained"
                disabled={!formik.isValid || formik.isSubmitting}
              >
                Speichern
              </Button>
            </Box>
          </BaseDialog>
        </Form>
      )}
    </Formik>
  );
};

export default UserFormModal;
