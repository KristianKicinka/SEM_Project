/**
 * @file Users.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";
import AuthUser from "../../../AuthUser";

import CreateUser from "./partials/users/CreateUser";
import UpdateUser from "./partials/users/UpdateUser";
import DeleteUser from "./partials/users/DeleteUser";

// Table headers
const columnNames = ["ID","Name", "Surname", "Email", "Phone", "Role"];
const dataIndexes = ["id","name", "surname", "email", "phone", "role"];


const Users = () => {

    const [users, setUsers] = useState([]);
    const {http, token} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);
    const [userOnDelete, setUserOnDelete] = useState(null);
    const [userOnUpdate, setUserOnUpdate] = useState(null);

    const [createModalShow, setCreateModalShow] = useState(false);
    const [updateModalShow, setUpdateModalShow] = useState(false);
    const [deleteModalShow, setDeleteModalShow] = useState(false);

    /**
     * @brief The function ensures handling create button onclick event
     */
    const handleCreateClick = () => {
        setCreateModalShow(true);
    }

    /**
     * @brief The function ensures handling update button onclick event
     * @param {*} user User object to update
     */
    const handleUpdateClick = (user) => {
        setUserOnUpdate(user);
        setUpdateModalShow(true);
    }

    /**
     * @brief The function ensures handling delete button onclick event
     * @param {*} user User object to delete
     */
    const handleDeleteClick = (user) => {
        setUserOnDelete(user);
        setDeleteModalShow(true);  
    }

    const buttons = new Map([
        ["createButton", {name:"Create user", funct_call:handleCreateClick}],
        ["deleteButton", handleDeleteClick],
        ["updateButton", handleUpdateClick]
      ]);

    /**
     * @brief The function ensures fetching data from database
     */
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

    // Component body
    return (
        <div className="Dashboard container-fluid">
            <div className="row d-flex">
                <Sidebar sidebarType="admin" />
                <div className="col px-0" style={{flex: '1'}}>
                    <Navbar />
                    <div className="page container-fluid pt-md-3 px-4">
                        <CreateUser  
                            show={createModalShow}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setCreateModalShow(false)}
                        />
                        <UpdateUser 
                            show={updateModalShow} 
                            user={userOnUpdate}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setUpdateModalShow(false)}
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