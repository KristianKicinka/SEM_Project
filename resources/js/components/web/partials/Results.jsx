import React from "react";
import ReactDOM from "react-dom";
import { Modal, Button } from "react-bootstrap";

import CopyClipboard from "./CopyClipboard";

const Results = ({ show, handleClose, results, hashTypes }) => {

    console.log(hashTypes);

    const hashItems = (hashType, hashes) => {
        return (
            <li className="list-group-item" key={hashType}>
                <b>{hashType} Hashes : </b>
                <ul className="list-group list-group-flush">
                    {hashes?.map((hash, id) => {
                        return (
                            <li className="list-group-item" key={id} >
                                
                            </li>
                        );
                    })}
                </ul>
            </li>
        );
    }

    const resultItem = (key, data, hashTypes) => {
        return (
            <li key={key} >
                {resultTable(data, hashTypes)}
            </li>
        );
    }

    const resultTable = (data, hashTypes) => {
        console.log(hashTypes);
        return (
            <table className="table">
                <thead>
                    <tr>
                        <th>App name</th>
                        <th>Pcakage name</th>
                        <th>Version</th>
                        {(hashTypes.includes("JA3")) ? <th>JA3 hash</th> : null}
                        <th>SNI</th>
                        {(hashTypes.includes("JA3S")) ? <th>JA3S hash</th> : null}
                    </tr>
                </thead>
                <tbody>
                    {data.map((row) => dataItem(row))}
                </tbody>
            </table>
        );
    }

    const dataItem = (row) => {
        console.log(row.ja3_hash);
        return (
            <tr>
                <td>{row.app_name}</td>
                <td>{row.package_name}</td>
                <td>{row.app_version}</td>
                {(hashTypes.includes("JA3")) ? <td><CopyClipboard text={row.ja3_hash}/></td> : null}
                <td><CopyClipboard text={row.sni}/></td>
                {(hashTypes.includes("JA3S")) ? <td><CopyClipboard text={row.ja3s_hash}/></td> : null}
            </tr>
        );
    }

    return (
        <div className="Results">
            <Modal size="xl" dialogClassName="modal-85w" show={show} onHide={handleClose} aria-labelledby="result-modal">
                <Modal.Header closeButton>
                    <Modal.Title id="result-modal">Results</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <ul className="list-unstyled">
                        {Object.keys(results).map((key, index) => resultItem(index, results[key], hashTypes))}
                    </ul>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default Results;
