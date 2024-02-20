import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import { Modal, Button, Spinner, ProgressBar } from "react-bootstrap";

const LoadingModal = ({ show, handleClose, loadingData }) => {

    const header = "Hash creation in progress...";

    const loadingItem = (key, name, progress, message) => {
        return (
            <li key={key}>
               <div className="container">
                    <div className="row">
                        <div className="col">
                            <p>{name ? name+' :' : null}</p>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col">
                            <ProgressBar 
                                now={progress} 
                                label={`${progress}%`} 
                            />
                        </div>
                    </div>
                    <div className="row py-3">
                        <div className="col" />
                            <div className="col-auto">
                                <b>{message}</b>
                            </div>
                        <div className="col" />
                    </div> 
               </div>
            </li>
        );
    }

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
                            <div className="container">
                                <ul className="list-unstyled">
                                    {Object.keys(loadingData).map((data_key, index) => 
                                        loadingItem(
                                            index, loadingData[data_key]?.name,
                                            loadingData[data_key]?.progress,
                                            loadingData[data_key]?.message)
                                    )}
                                </ul>
                            </div>
                        </div>
                    </div>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default LoadingModal;
