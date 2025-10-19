/**
 * @file Hashes.jsx
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

import CreateHash from "./partials/hashes/CreateHash";
import DeleteHash from "./partials/hashes/DeleteHash";
import UpdateHash from "./partials/hashes/UpdateHash";
import Ja4xInfo from "../partials/Ja4xInfo";

// Table headers
const columnNames = [
    "ID", "App Name", "Package name", "Version", "Src IP", "Src port", "Dest IP", "Dest port", "SNI", "JA3 hash",
    "JA3S hash", "JA4 hash", "JA4S hash", "JA4X hash", "Is dangerous", "Is malware"
];
const dataIndexes = [
    "id", "app_name", "package_name", "version", "ip_src", "port_src", "ip_dest", "port_dest", "sni", "ja3_hash",
    "ja3s_hash", "ja4_hash", "ja4s_hash", "ja4x_hash", "is_dangerous", "is_malware"
];


const Hashes = () => {

    const [hashes, setHashes] = useState([]);
    const {http, token} = AuthUser();
    const [fetchDataState, setFetchDataState] = useState(false);

    const [hashOnDelete, setHashOnDelete] = useState(null);
    const [hashOnUpdate, setHashOnUpdate] = useState(null);

    const [createModalShow, setCreateModalShow] = useState(false);
    const [updateModalShow, setUpdateModalShow] = useState(false);
    const [deleteModalShow, setDeleteModalShow] = useState(false);

    /**
     * @brief The function ensures handling create button on click event
     */
    const handleCreateClick = () => {
        setCreateModalShow(true);
    }

    /**
     * @brief The function ensures handling delete button on click event
     * @param {*} hash Hash object to delete
     */
    const handleDeleteClick = (hash) => {
        setHashOnDelete(hash);
        setDeleteModalShow(true);
    }

    /**
     * @brief The function ensures handling update button on click event
     * @param {*} hash Hash object to update
     */
    const handleUpdateClick = (hash) => {
        setHashOnUpdate(hash);
        setUpdateModalShow(true);
    }

    const buttons = new Map([
        ["createButton", {name:"Create hash", funct_call:handleCreateClick}],
        ["deleteButton", handleDeleteClick],
        ["updateButton", handleUpdateClick]
      ]);

    /**
     * @brief The function ensures fetching data from database
     */
    const fetchData = async () => {
        try {
            let resp = await http.post('/admin/hashes');
            setHashes(resp.data);
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
            <div className="row d-flex" >
                <Sidebar sidebarType="admin" />
                <div className="col px-0" style={{flex: '1', minWidth: '0'}}>
                    <Navbar />
                    <div className="page container-fluid pt-md-3 px-4">
                        <CreateHash
                            show={createModalShow}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setCreateModalShow(false)}
                        />
                        <DeleteHash
                            show={deleteModalShow}
                            hash={hashOnDelete}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setDeleteModalShow(false)}
                        />
                        <UpdateHash
                            show={updateModalShow}
                            hash={hashOnUpdate}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setUpdateModalShow(false)}
                        />
                        <TableComponent
                            data={hashes}
                            dataIndexes={dataIndexes}
                            columnNames={columnNames}
                            buttons={buttons}
                            tableName={"Hashes"}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Hashes;
