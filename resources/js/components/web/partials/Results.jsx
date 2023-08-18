import React from "react";
import ReactDOM from "react-dom";
import { Modal, Button } from "react-bootstrap";

import CopyClipboard from "./CopyClipboard";

const Results = ({ show, handleClose, results, hashTypes }) => {
    console.log(results.hashes);

    const hashItems = (hashType, hashes) => {
        return (
            <li className="list-group-item" key={hashType}>
                <b>{hashType} Hashes : </b>
                <ul className="list-group list-group-flush">
                    {hashes?.map((hash, id) => {
                        return (
                            <li className="list-group-item" key={id} >
                                <CopyClipboard text={hash}/>
                            </li>
                        );
                    })}
                </ul>
            </li>
        );
    }

    return (
        <div className="Results">
            <Modal show={show} onHide={handleClose}>
                <Modal.Header closeButton>
                    <Modal.Title>Results</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <ul className="list-group">
                        <li className="list-group-item">
                            <b>App name : </b> {results.app_name}
                        </li>
                        <li className="list-group-item">
                            <b>Package name : </b> {results.package_name}
                        </li>
                        <li className="list-group-item">
                            <b>Version name : </b> {results.app_version}
                        </li>
                        {results.JA3_hashes?.length !== 0 && hashItems('JA3', results.JA3_hashes)}
                        {results.JA3S_hashes?.length !== 0 && hashItems('JA3S', results.JA3S_hashes)}
                        {results.FlowMon_hashes?.length !== 0 && hashItems('FlowMon', results.FlowMon_hashes)}
                    </ul>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default Results;
