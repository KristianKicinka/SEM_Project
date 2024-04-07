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

const API = () => {

    // Table headers
    const columnNames = ["ID","User", "IP address", "Request type", "Status"];
    const dataIndexes = ["id", "email", "ip_address", "type", "status"];

    const [apiRequests, setApiRequests] = useState([]);
    const { http, token, user } = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);
    const buttons = new Map([]);

    /**
     * @brief The function ensures fetching data from database
     */
    const fetchData = async () => {
        try {
            let resp = await http.post('/user/api-requests', {user_id: user.id});
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
        <div className="Dashboard container-fluid">
            <div className="row">
                <Sidebar sidebarType="basic_user" />
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