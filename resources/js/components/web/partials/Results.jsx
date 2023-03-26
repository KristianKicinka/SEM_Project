import React from "react";
import ReactDOM from "react-dom";
import { Modal, Button } from "react-bootstrap";

const Results = ({ show, handleClose, results, hashTypes }) => {
    console.log(results.hashes);

    return (
        <div className="Results">
            <Modal show={show} onHide={handleClose}>
                <Modal.Header closeButton>
                    <Modal.Title>Results</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <ul className="list-group">
                        <li className="list-group-item">
                            <b>APK name : </b> {results.apk_name}
                        </li>
                        <li className="list-group-item">
                            <b>Package name : </b> {results.package_name}
                        </li>
                        <li className="list-group-item">
                            <b>Version name : </b> {results.version_name}
                        </li>
                        {hashTypes.map((hashType, key) => {
                            return (
                                <li className="list-group-item" key={key}>
                                    <b>{hashType} Hashes : </b>
                                    <ul className="list-group list-group-flush">
                                        {results.hashes?.[hashType].map((hash, id) => {
                                            return (
                                                <li className="list-group-item" key={id} >
                                                    {hash}
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </li>
                            );
                        })}
                    </ul>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default Results;
