/**
 * @file Emulators.jsx
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
import CreateEmulator from "./partials/emulators/CreateEmulator";
import DeleteEmulator from "./partials/emulators/DeleteEmulator";

// Table headers
const columnNames = ["ID", "Emulator name", "Docker ID", "Network interface", "Working state", "Memory [MB]", "CPU count", "Emulator status"];
const dataIndexes = ["id", "name", "docker_id", "network_interface", "is_working", "memory", "cpu_count", "status"];


const Emulators = () => {

    const [emulators, setEmulators] = useState([]);
    const {http, token} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);
    const [emulatorOnDelete, setEmulatorOnDelete] = useState(null);

    const [deleteModalShow, setDeleteModalShow] = useState(false);
    const [createModalShow, setCreateModalShow] = useState(false);

    /**
     * @brief The function ensures handling create button onclick event
     */
    const handleCreateClick = () => {
        setCreateModalShow(true);
    }

    /**
     * @brief The function ensures handling delete button on click events
     * @param {*} hash Hash to delete
     */
    const handleDeleteClick = (hash) => {
        setEmulatorOnDelete(hash);
        setDeleteModalShow(true);
    }

    /**
     * @brief The function ensures stopping an emulator
     * @param {*} emulatorData Emulator data
     */
    const handleStopClick = async (emulatorData) => {

        try {
            await http.post("/admin/emulator/stop", { container_name: emulatorData.name });
            setFetchDataState((prevState) => !prevState); // Refresh data
        } catch (error) {
            console.log("Error stopping emulator:", error);
        }
    };

    /**
     * @brief The function ensures starting an emulator
     * @param {*} emulatorData Emulator data
     */
    const handleStartClick = async (emulatorData) => {
        try {
            await http.post("/admin/emulator/start", { container_name: emulatorData.name });
            setFetchDataState((prevState) => !prevState); // Refresh data
        } catch (error) {
            console.log("Error starting emulator:", error);
        }
    };

    /**
     * @brief Buttons configuration for the table component
     */
    const buttons = new Map([
        ["createButton", { name: "Create Emulator", funct_call: handleCreateClick }],
        ["deleteButton", handleDeleteClick],
        ["stopButton", handleStopClick ],
        ["startButton", handleStartClick],
    ]);

    /**
     * @brief Fetches the status of a specific emulator by its name.
     * @param {string} emulatorName Name of the emulator (container)
     */
    const fetchEmulatorStatus = async (emulatorName) => {
        try {
            const response = await http.post("/admin/emulator/status", { container_name: emulatorName });
            console.log(response.data)
            return response.data.container_status || "unknown";
        } catch (error) {
            console.log(`Error fetching status for ${emulatorName}:`, error);
            return "unknown";
        }
    };

    /**
     * @brief The function ensures fetching data from database system
     */
    const fetchData = async () => {
        try {
            const resp = await http.post("/admin/emulators");
            const emulatorsWithStatus = await Promise.all(
                resp.data.map(async (emulator) => ({
                    ...emulator,
                    status: await fetchEmulatorStatus(emulator.name),
                }))
            );
            setEmulators(emulatorsWithStatus);
        } catch (error) {
            console.log("Error fetching emulators:", error);
        }
    }
    
    useEffect(() => {
        fetchData();
        const interval = setInterval(() => {fetchData()}, 3000);
        return () => clearInterval(interval);
    }, [fetchDataState]);

    // Component body
    return (
        <div className="Emulators container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="container-fluid">
                        <CreateEmulator  
                            show={createModalShow}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setCreateModalShow(false)}
                        />

                        <DeleteEmulator
                            show={deleteModalShow} 
                            emulator={emulatorOnDelete}
                            setFetchDataState={setFetchDataState}
                            handleClose={() => setDeleteModalShow(false)}
                        />

                        <TableComponent
                            data={emulators} 
                            dataIndexes={dataIndexes} 
                            columnNames={columnNames} 
                            buttons={buttons}
                            tableName={"Emulators"}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Emulators;