import React from "react";
import ReactDOM from "react-dom";
import { Modal, Button } from "react-bootstrap";

import CopyClipboard from "./CopyClipboard";

const Results = ({ show, handleClose, results, hashTypes }) => {

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

    const resultItem = (key, data) => {
        return (
            <li key={key} >
                <ul className="list-group">
                    <li className="list-group-item">
                        <b>App name : </b> {data.app_name}
                    </li>
                    <li className="list-group-item">
                        <b>Package name : </b> {data.package_name}
                    </li>
                    <li className="list-group-item">
                        <b>Version name : </b> {data.app_version}
                    </li>
                    {data.JA3_hashes?.length !== 0 && hashItems('JA3', data.JA3_hashes)}
                    {data.JA3S_hashes?.length !== 0 && hashItems('JA3S', data.JA3S_hashes)}
                    {data.FlowMon_hashes?.length !== 0 && hashItems('FlowMon', data.FlowMon_hashes)}
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
                    <ul className="list-unstyled">
                        {Object.keys(results).map((key, index) => resultItem(index, results[key]))}
                    </ul>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default Results;
