/* eslint-disable @typescript-eslint/no-explicit-any */
import { AxiosResponse, Method } from "axios";
import { axiosInstance } from "./config";
import { enqueueSnackbar } from "notistack";

interface RequestOptions {
  method: Method;
  endpoint: string;
  data?: any;
  params?: any;
  headers?: Record<string, string>;
  onUnauthorized?: () => void;
}

interface SuccessResponse<T = any> {
  status: true;
  message?: string;
  data: T;
}

interface ErrorResponse {
  status: false;
  message: string;
  data?: any;
}

type ApiResponse<T> = SuccessResponse<T> | ErrorResponse;

export async function handleRequest<T = any>({
  method,
  endpoint,
  data,
  params,
  headers,
  onUnauthorized,
}: RequestOptions): Promise<T> {
  try {
    const response: AxiosResponse<ApiResponse<T>> = await axiosInstance({
      method,
      url: endpoint,
      data,
      params,
      headers,
    });

    if (response.data.status) {
      return response.data.data;
    } else {
      const errorMessage = extractErrorMessage(response.data);
      showErrorMessage(errorMessage);
      throw new Error(errorMessage);
    }
  } catch (error: any) {
    if (error.response) {
      const { status, data } = error.response;

      if (status === 401 || status === 419) {
        // For login endpoints, preserve the error data
        if (endpoint === "/login" || endpoint.includes("/auth")) {
          const errorMessage = extractErrorMessage(data);
          const error = new Error(errorMessage);
          (error as any).response = { data, status };
          throw error;
        }
        
        // For other endpoints, handle unauthorized normally
        if (typeof onUnauthorized === "function") {
          onUnauthorized();
        }
        throw new Error("Unauthenticated");
      }

      const errorMessage = extractErrorMessage(data);
      showErrorMessage(errorMessage);
      throw new Error(errorMessage);
    } else if (error.request) {
      enqueueSnackbar("Keine Antwort vom Server.", { variant: "error" });
      throw new Error("Keine Antwort vom Server.");
    } else {
      enqueueSnackbar("Unerwarteter Fehler.", { variant: "error" });
      throw new Error(error.message);
    }
  }
}

function extractErrorMessage(data: any): string {
  // Handle validation errors (Laravel style)
  if (data?.data && typeof data.data === 'object') {
    // Check if it's a validation error with field-specific messages
    const fieldErrors = Object.values(data.data).flat();
    if (fieldErrors.length > 0 && typeof fieldErrors[0] === 'string') {
      return fieldErrors[0];
    }
    
    // Check if it's a nested message structure
    if (data.data.message) {
      return data.data.message;
    }
  }
  
  // Handle regular error messages
  return (
    data?.message ||
    data?.data?.message ||
    "Ein unbekannter Fehler ist aufgetreten."
  );
}

function showErrorMessage(message: string) {
  enqueueSnackbar(message, { variant: "error" });
}
