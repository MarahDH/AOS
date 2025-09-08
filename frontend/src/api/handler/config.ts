import axios from "axios";
import { Cookies } from "react-cookie";

const cookies = new Cookies();

export const axiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  timeout: 10000,
  headers: {
    "Content-Type": "application/json",
  },
});

// Helper function to create axios instance with disabled redirects
export const createAxiosInstanceWithoutRedirects = () => {
  const instance = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    withCredentials: true,
    timeout: 10000,
    headers: {
      "Content-Type": "application/json",
    },
  });
  
  // Add response interceptor without 401 redirect
  instance.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 403) {
        //   showErrorAlert("You don't have permission to perform this action");
      }
      return Promise.reject(error);
    }
  );
  
  return instance;
};

// // Add a request interceptor
axiosInstance.interceptors.request.use(
  (config) => {
    const token = cookies.get("token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    // Don't redirect on 401 for login endpoints
    if (error.response?.status === 401) {
      const isLoginEndpoint = error.config?.url?.includes('/login') || 
                             error.config?.url?.includes('/auth');
      
      if (!isLoginEndpoint) {
        window.location.href = "/anmelden";
      }
    }
    if (error.response?.status === 403) {
      //   showErrorAlert("You don't have permission to perform this action");
    }
    return Promise.reject(error);
  }
);
