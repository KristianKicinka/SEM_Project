import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import { Modal, Button, Spinner, ProgressBar } from "react-bootstrap";

const LoadingModal = ({ show, handleClose, loadingData }) => {

    const header = "Hash creation in progress...";

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
                                <ProgressBar 
                                    now={loadingData.progress} 
                                    label={`${loadingData.progress}%`} 
                                />
                            </div>
                        </div>
                        <div className="row py-3">
                            <div className="col" />
                            <div className="col-auto">
                                <b>{loadingData.message}</b>
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
