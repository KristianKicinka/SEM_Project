import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";

import AuthUser from "../../../AuthUser";

//import CreateHash from "./partials/hashes/CreateHash";
//import DeleteHash from "./partials/hashes/DeleteHash";

const columnNames = ["ID","User", "IP address", "Request type"];
const dataIndexes = ["id", "email", "ip_address", "type"];

const API = () => {

    const [apiRequests, setApiRequests] = useState([]);
    const {http, token} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);

    const [createModalShow, setCreateModalShow] = useState(false);
    const [deleteModalShow, setDeleteModalShow] = useState(false);

    const handleCreateClick = () => {
        setCreateModalShow(true);
    }

    const handleDeleteClick = (request) => {
        setRequestOnDelete(request);
        setDeleteModalShow(true);  
    }

    const buttons = new Map([
        ["createButton", handleCreateClick],
        ["deleteButton", handleDeleteClick],
      ]);

    const fetchData = async () => {
        try {
            let resp = await http.post('/admin/requests');
            console.log(resp.data)
            setApiRequests(resp.data);
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
        <div className="API container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container">
    
                        <TableComponent 
                            data={apiRequests} 
                            dataIndexes={dataIndexes} 
                            columnNames={columnNames} 
                            buttons={buttons}
                            tableName={"API Requests"}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default API;