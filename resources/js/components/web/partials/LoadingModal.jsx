import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import { Modal, Badge } from "react-bootstrap";
import { toast } from 'react-toastify';

import ProgressBar from 'react-bootstrap/ProgressBar';
import pusher from "../../../pusher";
import Results from "../partials/Results";


const LoadingModal = ({ channel_id, processes, onClose , hashTypes }) => {

    const [updatedProcesses, setUpdatedProcesses] = useState(processes);
    const [showResults, setShowResults] = useState(false);
    const [results, setResults] = useState([]);

    useEffect(() => {

        const channel = pusher.subscribe(`process-channel-${channel_id}`);
        console.log(channel);
        channel.bind('process-update', data => {

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
     * @brief The function
     */
    const closeResults = () => {
        setShowResults(false);
    }

    /**
     * @brief 
     * @param {*} process_id 
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
     * @brief The function ensures 
     * @param {*} process 
     * @returns 
     */
    const loadingItem = (process) => {
        return (
            <tr key={process.process_id} className="align-middle gx-5">
                <td className="col">{process.name}</td>
                <td className="col-md-3 text-center"><ProgressBar now={process.progress} label={`${process.progress}%`} /></td>
                <td className="col text-center">{process.message}</td>
                <td className="col">{process.status}</td>
                <td className="col text-center">
                    {process.status === 'failed' && <Badge bg="danger">Process failed!</Badge>}
                    {process.status === 'finished' && ( <button className="btn btn-sm btn-search text-light" onClick={() => getResults(process.process_id)}>Show Results</button>)}
                </td>
            </tr>
        );
    }

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
            {showResults && (<Results results={results} onClose={closeResults} hashTypes={hashTypes} />)}
        </div>
    );
};

export default LoadingModal;
