import AddIcon from "@mui/icons-material/Add";
import CardBox from "@components/CardBox";
import ChangePasswordModal, {
  ChangePasswordFormValues,
} from "./New/ChangePasswordModal";
import ConfirmationDialog from "@components/ConfirmationDialog";
import IconAction from "@components/IconAction";
import RoundedIconButton from "@components/RoundedIconButton";
import UserFormModal, { UserFormValues } from "./New/UserFormModal";
import { Box } from "@mui/material";
import { Delete, Edit, LockReset } from "@mui/icons-material";
import { FormMode } from "@enums/FormMode";
import { FunctionComponent, useEffect, useState } from "react";
import { MTable } from "@components/MTable";
import { useApiErrorHandler } from "@hooks/useApiErrorHandler";
import { UserColumns } from "./Columns";
import { UserModel } from "@interfaces/User.model";
import { UsersApi } from "@api/users";
import { enqueueSnackbar } from "notistack";

const UsersPage: FunctionComponent = () => {
  const { showError } = useApiErrorHandler();
  const [modalOpen, setModalOpen] = useState(false);
  const [editMode, setEditMode] = useState<FormMode>(FormMode.CREATE);
  const [selectedUser, setSelectedUser] = useState<UserFormValues | null>(null);
  const [changePasswordOpen, setChangePasswordOpen] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [users, setUsers] = useState<UserModel[]>([]);
  const [loading, setLoading] = useState(false);

  const fetchUsers = async () => {
    try {
      setLoading(true);
      const data = await UsersApi.getAllUsers();
      setUsers(data);
    } catch (error) {
      showError(error);
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (user: UserModel) => {
    setSelectedUser({
      id: user.id,
      name: user.name,
      email: user.email,
      password: "",
      confirmPassword: "",
      role_id: user.role.id,
    });
    setEditMode(FormMode.EDIT);
    setModalOpen(true);
  };

  const handleAdd = () => {
    setSelectedUser(null);
    setEditMode(FormMode.CREATE);
    setModalOpen(true);
  };

  const handleDelete = (user: UserModel) => {
    setSelectedUser({
      id: user.id,
      name: user.name,
      email: user.email,
      password: "",
      confirmPassword: "",
      role_id: user.role.id,
    });
    setDeleteDialogOpen(true);
  };

  const confirmDelete = async () => {
    if (selectedUser?.id) {
      try {
        await UsersApi.deleteUser(selectedUser.id);
        enqueueSnackbar("Benutzer erfolgreich gelöscht", {
          variant: "success",
        });
        await fetchUsers();
      } catch (error) {
        showError(error);
      } finally {
        setDeleteDialogOpen(false);
        setSelectedUser(null);
      }
    }
  };

  const handleChanagePassword = async (data: ChangePasswordFormValues) => {
    try {
      const res = await UsersApi.changePassword({
        newPassword: data.newPassword,
        newPassword_confirmation: data.newPassword_confirmation,
      });
      setChangePasswordOpen(false);
      setSelectedUser(null);
      enqueueSnackbar(res.message, { variant: "success" });
    } catch (error) {
      showError(error);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  return (
    <Box p={2}>
      <CardBox label="Benutzer">
        <MTable
          data={users}
          searchableField="name"
          columns={UserColumns}
          loading={loading}
          actions={(row) => (
            <>
              <IconAction tooltip="Bearbeiten" onClick={() => handleEdit(row)}>
                <Edit fontSize="small" />
              </IconAction>
              <IconAction tooltip="Löschen" onClick={() => handleDelete(row)}>
                <Delete fontSize="small" />
              </IconAction>
              <IconAction
                tooltip="Passwort ändern"
                onClick={() => setChangePasswordOpen(true)}
              >
                <LockReset fontSize="small" />
              </IconAction>
            </>
          )}
        />
        <RoundedIconButton
          icon={<AddIcon fontSize="small" />}
          label="NEU"
          onClick={handleAdd}
        />

        <UserFormModal
          open={modalOpen}
          onClose={() => setModalOpen(false)}
          onSuccess={() => {
            fetchUsers();
            setModalOpen(false);
            setSelectedUser(null);
          }}
          initialValues={selectedUser || undefined}
          mode={editMode}
        />

        <ChangePasswordModal
          open={changePasswordOpen}
          onClose={() => setChangePasswordOpen(false)}
          onSubmit={handleChanagePassword}
        />

        <ConfirmationDialog
          open={deleteDialogOpen}
          onClose={() => setDeleteDialogOpen(false)}
          onConfirm={confirmDelete}
          title="Benutzer löschen"
          message="Möchten Sie diesen Benutzer wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden."
        />
      </CardBox>
    </Box>
  );
};

export default UsersPage;
