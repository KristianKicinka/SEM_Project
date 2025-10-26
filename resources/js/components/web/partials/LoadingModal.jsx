/**
 * @file LoadingModal.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import { Modal, Badge } from "react-bootstrap";
import { toast } from 'react-toastify';

import ProgressBar from 'react-bootstrap/ProgressBar';
import pusher from "../../../pusher";
import Results from "../partials/Results";


const LoadingModal = ({ channel_id, processes, onClose , hashTypes, customHashTypes = [] }) => {

    const [updatedProcesses, setUpdatedProcesses] = useState(processes);
    const [showResults, setShowResults] = useState(false);
    const [results, setResults] = useState([]);

    useEffect(() => {

        const channel = pusher.subscribe(`process-channel-${channel_id}`);
        channel.bind('process-update', data => {

            // Setting new updated data to processes
            setUpdatedProcesses(prevProcesses => {
                const index = prevProcesses.findIndex(process => process.process_id === data.process_id);

                if (index !== -1){
                    const updatedProcesses = [...prevProcesses];
                    updatedProcesses[index] = { ...updatedProcesses[index], ...data };
                    return updatedProcesses;
                }else{
                    return [...prevProcesses, data];
                }
            });
        });

        return () => {
            pusher.unsubscribe(`process-channel-${channel_id}`);
        };
    }, [channel_id]);

    /**
     * @brief The function ensures colse results modal box
     */
    const closeResults = () => {
        setShowResults(false);
    }

    /**
     * @brief The function ensures getting process generation results
     * @param {*} process_id Process ID
     */
    const getResults = async (process_id) => {
        try {
            const data = new FormData();
            data.append("process_id", process_id);

            let results = await axios.post('/api/get-process-results', data);
            
            setResults(results.data);
            setShowResults(true);
        } catch (error) {
            onClose()
            toast.error('Hash generation error!');
            console.log(`ERROR: ${error}`);
        }
    }

    /**
     * @brief The function ensures loading items creation
     * @param {*} process Process data object
     * @returns Loading item component
     */
    const loadingItem = (process) => {

        let is_processing = process.status === 'processing';

        return (
            <tr key={process.process_id} className="align-middle gx-5">
                <td className="col">{process.name}</td>
                <td className="col-md-3 text-center"><ProgressBar now={process.progress} label={`${process.progress}%`} /></td>
                <td className="col text-center">{process.message}</td>
                <td className="col">{process.status}</td>
                <td className="col text-center">
                    {is_processing && <span className="spinner-border spinner-border-sm text-orange" role="status" aria-hidden="true"></span>}
                    {process.status === 'failed' && <Badge bg="danger">Process failed!</Badge>}
                    {process.status === 'finished' && ( <button className="btn btn-sm btn-search text-light" onClick={() => getResults(process.process_id)}>Show Results</button>)}
                </td>
            </tr>
        );
    }

    // Component body
    return (
        <div className="LoadingModal">
            <Modal show={true} onHide={onClose} size="xl" dialogClassName="modal-85w">
                <Modal.Header closeButton>
                    <Modal.Title className="px-md-4">Hash creation process</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="container">
                        <div className="row">
                            <div className="container">
                                <div className="table-responsive">
                                <table className="table px-2">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th className="text-center">Progress</th>
                                            <th className="text-center">Info</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {updatedProcesses.map((process) => loadingItem(process))}
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </Modal.Body>
            </Modal>
            {showResults && (<Results results={results} onClose={closeResults} hashTypes={hashTypes} customHashTypes={customHashTypes} />)}
        </div>
    );
};

export default LoadingModal;
