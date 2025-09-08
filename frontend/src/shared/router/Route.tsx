import { createBrowserRouter } from "react-router-dom";
import { Offers, OfferForm, Users } from "./LazyRouter";
// Components
import LoginPage from "@pages/Auth/Login";
import Layout from "@components/Layout/Layout";
import UnauthorizedPage from "@pages/Errors/UnauthorizedPage";
import ProtectedPermissionRoute from "@components/ProtectedPermissionRoute";
import GenericErrorPage from "@pages/Errors/GenericErrorPage";
import NotFoundPage from "@pages/Errors/NotFoundPage";

export const RoutePages = [
  {
    path: "/",
    element: (
      <ProtectedPermissionRoute action="view" subject="offers">
        <Offers />
      </ProtectedPermissionRoute>
    ),
    label: "Angebote/Aufträge",
  },
  {
    path: "/angebote/neu", //new offer
    element: (
      <ProtectedPermissionRoute action="create" subject="offer">
        <OfferForm />
      </ProtectedPermissionRoute>
    ),
    label: "Neuen Angebot erstellen",
  },
  {
    path: "/angebote/:id", // offer detials
    element: (
      <ProtectedPermissionRoute action="update" subject="offer">
        <OfferForm />
      </ProtectedPermissionRoute>
    ),
    label: "Neuen Angebot erstellen",
  },
  {
    path: "/benutzer", // users
    element: (
      <ProtectedPermissionRoute action="view" subject="users">
        <Users />
      </ProtectedPermissionRoute>
    ),
    label: "Benutzer",
  },
  {
    path: "/unauthorized",
    element: <UnauthorizedPage />,
    label: "Unauthorized",
  },
];

export const router = createBrowserRouter(
  [
    {
      path: "/",
      element: <Layout />,
      errorElement: <GenericErrorPage />,
      children: RoutePages,
    },
    {
      path: "/anmelden",
      element: <LoginPage />,
    },
    {
      path: "*",
      element: <NotFoundPage />,
    },
  ]
);
