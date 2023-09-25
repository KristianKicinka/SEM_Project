import React, { useState } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";

const columnNames = ["ID","User", "requests count", "IP address"];
const dataIndexes = ["id", "user_name", "requests_count", "ip_address"];

const api_data = [
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},
    {id:1, user_name:"KristiánKičinka", requests_count:"101", ip_address:"124.111.121.32"},    
];


const API = () => {
    return (
        <div className="API container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container">
                        <TableComponent data={api_data} dataIndexes={dataIndexes} columnNames={columnNames} tableName={"API requests"} />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default API;