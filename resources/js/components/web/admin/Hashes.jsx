import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";

import AuthUser from "../../../AuthUser";

const columnNames = ["ID","Hash", "Hash type", "App Name", "Package name", "Version"];
const dataIndexes = ["id", "hash", "hash_type", "app_name", "package_name", "version"];

const Hashes = () => {

    const [hashes, setHashes] = useState([]);
    const {http, token} = AuthUser();

    const getHashes = async () => {
        try {
            let resp = await http.post('/admin/hashes');
            console.log(resp.data)
            setHashes(resp.data);
        } catch (error) {
            console.log(error);
        }
    }
    
    useEffect(() => {
        getHashes();
    }, []);

    return (
        <div className="Hashes container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container">
                        <TableComponent 
                            data={hashes} 
                            dataIndexes={dataIndexes} 
                            columnNames={columnNames} 
                            tableName={"Hashes"}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Hashes;