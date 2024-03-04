import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";

import AuthUser from "../../../AuthUser";

import CreateHash from "./partials/hashes/CreateHash";
import DeleteHash from "./partials/hashes/DeleteHash";
import UpdateHash from "./partials/hashes/UpdateHash";

const columnNames = ["ID", "SNI", "JA3 hash", "JA3S hash", "JA4 hash", "JA4S hash", "App Name", "Package name", "Version"];
const dataIndexes = ["id", "sni", "ja3_hash", "ja3s_hash", "ja4_hash", "ja4s_hash", "app_name", "package_name", "version"];

const Hashes = () => {

    const [hashes, setHashes] = useState([]);
    const {http, token} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);

    const [hashOnDelete, setHashOnDelete] = useState(null);
    const [hashOnUpdate, setHashOnUpdate] = useState(null);

    const [createModalShow, setCreateModalShow] = useState(false);
    const [updateModalShow, setUpdateModalShow] = useState(false);
    const [deleteModalShow, setDeleteModalShow] = useState(false);

    const handleCreateClick = () => {
        setCreateModalShow(true);
    }

    const handleDeleteClick = (hash) => {
        setHashOnDelete(hash);
        setDeleteModalShow(true);
    }

    const handleUpdateClick = (hash) => {
        setHashOnUpdate(hash);
        setUpdateModalShow(true);
        //console.log(hash);
    }

    const buttons = new Map([
        ["createButton", handleCreateClick],
        ["deleteButton", handleDeleteClick],
        ["updateButton", handleUpdateClick]
      ]);

    const fetchData = async () => {
        try {
            let resp = await http.post('/admin/hashes');
            //console.log(resp.data)
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

    return (
        <div className="Hashes container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container-fluid">
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