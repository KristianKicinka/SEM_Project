/**
 * @file API.jsx
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


// Table headers
const columnNames = ["ID","User", "IP address", "Request type", "Status"];
const dataIndexes = ["id", "email", "ip_address", "type", "status"];


const API = () => {

    const [apiRequests, setApiRequests] = useState([]);
    const {http, token} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);
    const [deleteModalShow, setDeleteModalShow] = useState(false);

    /**
     * @brief The function ensures handling delete button on click events
     * @param {*} request API request to delete
     */
    const handleDeleteClick = (request) => {
        setRequestOnDelete(request);
        setDeleteModalShow(true);  
    }

    const buttons = new Map([
        ["deleteButton", handleDeleteClick],
    ]);

    /**
     * @brief The function ensures fetching data from database
     */
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

    // Component body
    return (
        <div className="API container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container-fluid">
    
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