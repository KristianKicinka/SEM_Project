import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import { Modal, Button, Spinner, ProgressBar } from "react-bootstrap";

const LoadingModal = ({ show, handleClose }) => {

    const [completed, setCompleted] = useState(0);
    const [description, setDescription] = useState("");

    const header = "Hash creation in progress...";

    const appNameDescription = [
        "Starting the process",
        "Getting an APK file",
        "Installing an application in a virtual environment",
        "Network communication analysis",
        "Creating application fingerprints",
        "Uploading fingerprints to the database system",
    ];

    useEffect(() => {
        let index = 0;
        let comp = 0;
        setInterval(() => {
            if(index > 5){
                comp = 0;
                index = 0;
            }
                
            setCompleted(comp);
            setDescription(appNameDescription[index]);
            index++;
            comp += 20;
        }, 2000);
    }, []);

    return (
        <div className="LoadingModal">
            <Modal show={show}>
                <Modal.Body>
                    <div className="container p-4">
                        <div className="row pb-4">
                            <div className="col" />
                            <div className="col-auto">
                                <h3>{header}</h3>
                            </div>
                            <div className="col" />
                        </div>
                        <div className="row">
                            <div className="col">
                                <ProgressBar now={completed} label={`${completed}%`} />
                            </div>
                        </div>
                        <div className="row py-3">
                            <div className="col" />
                            <div className="col-auto">
                                <b>{description}</b>
                            </div>
                            <div className="col" />
                        </div>
                    </div>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default LoadingModal;
