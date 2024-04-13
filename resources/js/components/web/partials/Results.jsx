/**
 * @file Results.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, {useState} from "react";
import ReactDOM from "react-dom";
import { Modal, Button } from "react-bootstrap";
import CopyClipboard from "./CopyClipboard";
import Ja4xInfo from "./Ja4xInfo";


const Results = ({ results, onClose, hashTypes }) => {

    const hasResults = results && results.length > 0;

    const [ja4xToShow, setJa4xToShow] = useState(null);
    const [showJa4xModal, setShowJa4xModal] = useState(false);

    /**
     * @brief The function ensures data item creation
     * @param {*} row Table row data
     * @param {*} index Data item index
     * @returns Table row component
     */
    const dataItem = (row, index) => {

        return (
            <tr key={index}>
                <td>{row.app_name}</td>
                <td>{row.package_name}</td>
                <td>{row.app_version}</td>
                <td>{row.sni}</td>
                {(hashTypes.includes("JA3")) ? <td><CopyClipboard text={row.ja3_hash}/></td> : null}
                {(hashTypes.includes("JA3S")) ? <td><CopyClipboard text={row.ja3s_hash}/></td> : null}
                {(hashTypes.includes("JA4")) ? <td><CopyClipboard text={row.ja4_hash}/></td> : null}
                {(hashTypes.includes("JA4S")) ? <td><CopyClipboard text={row.ja4s_hash}/></td> : null}
                {(hashTypes.includes("JA4X")) ? <td>
                    <button className="btn btn-sm btn-search text-light"
                            onClick={() => handleJA4XClick(row.ja4x_hash)}>show
                    </button>
                </td> : null}
            </tr>
        );
    }

    /**
     * @brief The function ensures handling JA4X show button on click event
     * @param {*} ja4x_hash_data Hash object to update
     */
    const handleJA4XClick = (ja4x_hash_data) => {
        setJa4xToShow(JSON.parse(ja4x_hash_data));
        setShowJa4xModal(true);
    }

    /**
     * @brief The function ensures colse JA4X modal box
     */
    const closeJa4xModal = () => {
        setShowJa4xModal(false);
    }

    // Results component body
    return (
        <div className="Results">
            <Modal size="xl" dialogClassName="modal-95w" show={true} onHide={onClose} aria-labelledby="result-modal" scrollable={true}>
                <Modal.Header closeButton>
                    <Modal.Title id="result-modal">Results</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <table className="table table-sm table-responsive">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Package name</th>
                                <th>Version</th>
                                <th>SNI</th>
                                {(hashTypes.includes("JA3")) ? <th>JA3 hash</th> : null}
                                {(hashTypes.includes("JA3S")) ? <th>JA3S hash</th> : null}
                                {(hashTypes.includes("JA4")) ? <th>JA4 hash</th> : null}
                                {(hashTypes.includes("JA4S")) ? <th>JA4S hash</th> : null}
                                {(hashTypes.includes("JA4X")) ? <th>JA4X hash</th> : null}
                            </tr>
                        </thead>
                    <tbody className="text-nowrap">
                        {hasResults ? results.map((row, key) => dataItem(row, key)):(
                            <tr>
                                <td colSpan={4+hashTypes.length}>No hashes found, repeat the process</td>
                            </tr>
                        )}
                    </tbody>
                </table>
                </Modal.Body>
            </Modal>
            {showJa4xModal && (<Ja4xInfo data={ja4xToShow} onClose={closeJa4xModal} />)}
        </div>
    );
};

export default Results;
