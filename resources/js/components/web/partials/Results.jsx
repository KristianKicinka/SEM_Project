import React from "react";
import ReactDOM from "react-dom";
import { Modal, Button } from "react-bootstrap";

import CopyClipboard from "./CopyClipboard";

const Results = ({ results, onClose, hashTypes }) => {

    console.log(hashTypes);

    const dataItem = (row, index) => {
        console.log(row.ja3_hash);
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
            </tr>
        );
    }

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
                                <th>Pcakage name</th>
                                <th>Version</th>
                                <th>SNI</th>
                                {(hashTypes.includes("JA3")) ? <th>JA3 hash</th> : null}
                                {(hashTypes.includes("JA3S")) ? <th>JA3S hash</th> : null}
                                {(hashTypes.includes("JA4")) ? <th>JA4 hash</th> : null}
                                {(hashTypes.includes("JA4S")) ? <th>JA4S hash</th> : null}
                            </tr>
                        </thead>
                    <tbody>
                        {results.map((row) => dataItem(row))}
                    </tbody>
                </table>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default Results;
