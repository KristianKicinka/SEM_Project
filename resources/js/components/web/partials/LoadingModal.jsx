import React from "react";
import ReactDOM from "react-dom";
import { Modal, Button, Spinner } from "react-bootstrap";

const LoadingModal = ({ show, handleClose }) => {
    return (
        <div className="LoadingModal">
            <Modal show={show}>
                <Modal.Body>
                    <div className="container p-2">
                        <div className="row">
                            <div className="col"></div>
                            <div className="col-auto">
                                <div className="row">
                                    <div className="col-1">
                                        <Spinner animation="border" role="status">
                                            <span className="visually-hidden">
                                                Loading data...
                                            </span>
                                        </Spinner>
                                    </div>
                                    <div className="col-auto mx-3">
                                        <div className="h3 text ml-2 mt-1 float-start">
                                            Hash creation in progress...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="col"></div>
                        </div>
                    </div>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default LoadingModal;
