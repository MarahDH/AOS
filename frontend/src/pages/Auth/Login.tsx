import * as Yup from "yup";
import AOSLog from "../../assets/aos-logo.png";
import LoginForm from "./LoginForm";
import { Box, Paper, Typography, Alert } from "@mui/material";
import { Form, Formik } from "formik";
import { useNavigate } from "react-router-dom";
import { useAuth } from "@contexts/AuthProvider";
import { useState } from "react";
import { AxiosError } from "axios";

interface LoginFormValues {
  email: string;
  password: string;
  remember: boolean;
}

const initialValues: LoginFormValues = {
  email: "",
  password: "",
  remember: false,
};

const validationSchema = Yup.object({
  email: Yup.string()
    .email("Ungültige E-Mail-Adresse")
    .required("E-Mail ist erforderlich"),
  password: Yup.string().required("Passwort ist erforderlich"),
});

const LoginPage = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [error, setError] = useState<string>("");

  const handleSubmit = async (
    values: LoginFormValues,
    { setSubmitting }: any
  ) => {
    try {
      setError(""); // Clear any previous errors
      await login(values.email, values.password);
      navigate("/");
    } catch (error: AxiosError | any) {
      setSubmitting(false);
      console.log(error);
      
      // Extract error message from the error object
      const errorMessage = error?.response?.data?.data?.message || 
                          error?.response?.data?.message || 
                          error?.message || 
                          "Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.";
      setError(errorMessage);
    }
  };

  return (
    <Box
      display="flex"
      alignItems="center"
      justifyContent="center"
      minHeight="100vh"
      bgcolor="#f9f9f9"
    >
      <Paper elevation={3} sx={{ p: 4, width: 400, borderRadius: 3 }}>
        <Box display="flex" justifyContent="center" mb={3}>
          <img src={AOSLog} alt="Sitemark" width={100} />
        </Box>

        <Typography variant="h5" fontWeight={600} gutterBottom>
          Anmelden
        </Typography>

        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}

        <Formik
          initialValues={initialValues}
          validationSchema={validationSchema}
          onSubmit={handleSubmit}
          enableReinitialize={false}
        >
          {(formik) => (
            <Form>
              <LoginForm formik={formik} />
            </Form>
          )}
        </Formik>
      </Paper>
    </Box>
  );
};

export default LoginPage;
