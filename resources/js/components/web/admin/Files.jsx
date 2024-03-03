import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";

import AuthUser from "../../../AuthUser";
import DeleteFile from "./partials/files/DeleteFile";

const columnNames = ["ID", "File name", "File type", "File path" ,"App Name"];
const dataIndexes = ["file_id", "file_name", "file_type", "file_path", "app_name"];

const Files = () => {

    const [files, setFiles] = useState([]);
    const {http, token} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);

    const [fileOnDelete, setFileOnDelete] = useState(null);

    const [deleteModalShow, setDeleteModalShow] = useState(false);


    const handleDeleteClick = (hash) => {
        setFileOnDelete(hash);
        setDeleteModalShow(true);
    }

    const buttons = new Map([
        ["deleteButton", handleDeleteClick],
      ]);

    const fetchData = async () => {
        try {
            let resp = await http.post('/admin/files');
            console.log(resp.data)
            setFiles(resp.data);
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
                        <DeleteFile 
                            show={deleteModalShow} 
                            file={fileOnDelete}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setDeleteModalShow(false)}
                        />
                        <TableComponent
                            data={files} 
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

export default Files;