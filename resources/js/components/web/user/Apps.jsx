/**
 * @file Apps.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";
import LikedApps from "./partials/apps/LikedApps";

import AuthUser from "../../../AuthUser";

// Table headers
const columnNames = [
    "ID", "App Name", "Package name", "Version", "SNI", "JA3 hash", "JA3S hash", "JA4 hash",
    "JA4S hash", "JA4X hash", "Is dangerous", "Is malware", "IP src", "Port src", "IP dest",
    "Port dest"
];
const dataIndexes = [
    "id", "app_name", "package_name", "app_version", "sni", "ja3_hash", "ja3s_hash", "ja4_hash",
    "ja4s_hash", "ja4x_hash", "is_dangerous", "is_malware", "ip_src", "port_src", "ip_dest",
    "port_dest"
];

const Apps = () => {

    const [hashes, setHashes] = useState([]);
    const {http, token, user} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);
    const [createModalShow, setCreateModalShow] = useState(false);
    const [likedApps, setLikedApps] = useState([]);

    /**
     * @brief The function ensures app creation handling
     */
    const handleCreateClick = async () => {

        try {
            let resp = await http.post('/user/get-liked-apps', {user_id:user.id});
            setLikedApps(resp.data);
            setCreateModalShow(true);
        } catch (error) {
            console.log(error);
        }
    }

    const buttons = new Map([
        ["createButton", {name:"Liked apps", funct_call:handleCreateClick}],
    ]);

    /**
     * @brief The function ensures fetching data from database
     */
    const fetchData = async () => {
        try {
            let resp = await http.post('/user/get-liked-apps-hashes', {user_id:user.id});
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
            <div className="row">
                <Sidebar sidebarType="basic_user" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container-fluid">
                        <LikedApps
                            show={createModalShow}
                            setFetchDataState={setFetchDataState}
                            likedApps={likedApps}
                            handleClose={() => setCreateModalShow(false)}

                        />
                        <TableComponent
                            data={hashes}
                            dataIndexes={dataIndexes}
                            columnNames={columnNames}
                            buttons={buttons}
                            tableName={"Liked apps Hashes"}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Apps;
