import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";
import AuthUser from "../../../AuthUser";

const columnNames = ["ID","Name", "Surname", "Email", "Phone", "Role"];
const dataIndexes = ["id","name", "surname", "email", "phone", "role"];


const Users = () => {

    const [users, setUsers] = useState([]);
    const {http, token} = AuthUser();

    const getHashes = async () => {
        try {
            let resp = await http.post('/admin/users');
            console.log(resp.data)
            setUsers(resp.data.users);
        } catch (error) {
            console.log(error);
        }
    }
    
    useEffect(() => {
        getHashes();
    }, []);

    return (
        <div className="Users container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container">
                        <TableComponent data={users} dataIndexes={dataIndexes} columnNames={columnNames} tableName={"Users"} />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Users;