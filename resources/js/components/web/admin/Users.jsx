import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";
import AuthUser from "../../../AuthUser";

import CreateUser from "./partials/users/CreateUser";
import DeleteUser from "./partials/users/DeleteUser";

const columnNames = ["ID","Name", "Surname", "Email", "Phone", "Role"];
const dataIndexes = ["id","name", "surname", "email", "phone", "role"];


const Users = () => {

    const [users, setUsers] = useState([]);
    const {http, token} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);

    const [userOnDelete, setUserOnDelete] = useState();
    const [userOnUpdate, setUserOnUpdate] = useState();

    const [createModalShow, setCreateModalShow] = useState(false);
    const [updateModalShow, setUpdateModalShow] = useState(false);
    const [deleteModalShow, setDeleteModalShow] = useState(false);

    const handleCreateClick = () => {
        setCreateModalShow(true);
    }

    const handleUpdateClick = (user) => {
        setUserOnUpdate(user);
        setUpdateModalShow(true);
    }

    const handleDeleteClick = (user) => {
        setUserOnDelete(user);
        setDeleteModalShow(true);  
    }

    const buttons = new Map([
        ["createButton", handleCreateClick],
        ["deleteButton", handleDeleteClick],
        ["updateButton", handleDeleteClick]
      ]);

    const fetchData = async () => {
        try {
            let resp = await http.post('/admin/users');
            console.log(resp.data)
            setUsers(resp.data.users);
        } catch (error) {
            console.log(error);
        }
    }
    
    useEffect(() => {
        fetchData();
        const interval = setInterval(() => {fetchData()}, 3000);
        return () => clearInterval(interval);
    }, [fetchDataState]);

    return (
        <div className="Users container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container">
                        <CreateUser  
                            show={createModalShow}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setCreateModalShow(false)}
                        />
                        <DeleteUser 
                            show={deleteModalShow} 
                            user={userOnDelete}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setDeleteModalShow(false)}
                        />
                        <TableComponent 
                            data={users} 
                            dataIndexes={dataIndexes} 
                            columnNames={columnNames}
                            buttons={buttons}
                            tableName={"Users"}
                             />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Users;