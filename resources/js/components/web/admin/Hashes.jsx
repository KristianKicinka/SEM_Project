import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";

import AuthUser from "../../../AuthUser";

import CreateHash from "./partials/hashes/CreateHash";
import DeleteHash from "./partials/hashes/DeleteHash";
//import DeleteHash from "./partials/hashes/DeleteHash";

const columnNames = ["ID", "JA3 hash", "SNI", "JA3S hash" ,"App Name", "Package name", "Version"];
const dataIndexes = ["id", "ja3_hash", "sni", "ja3s_hash", "app_name", "package_name", "version"];

const Hashes = () => {

    const [hashes, setHashes] = useState([]);
    const {http, token} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);

    const [hashOnDelete, setHashOnDelete] = useState(null);
    const [hashOnUpdate, setHashOnUpdate] = useState(null);

    const [createModalShow, setCreateModalShow] = useState(false);
    const [deleteModalShow, setDeleteModalShow] = useState(false);

    const handleCreateClick = () => {
        setCreateModalShow(true);
    }

    const handleDeleteClick = (hash) => {
        setHashOnDelete(hash);
        setDeleteModalShow(true);
    }

    const buttons = new Map([
        ["createButton", handleCreateClick],
        ["deleteButton", handleDeleteClick],
      ]);

    const fetchData = async () => {
        try {
            let resp = await http.post('/admin/hashes');
            console.log(resp.data)
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