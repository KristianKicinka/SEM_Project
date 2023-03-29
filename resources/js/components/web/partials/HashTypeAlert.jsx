import React, { useState } from "react";
import ReactDOM from "react-dom";

import Toast from 'react-bootstrap/Toast';

const HashTypeAlert = ({ showAlert, setShowAlert }) => {
    if (showAlert) {
        return (
            <div className="HashTypeAlert">
                <Toast onClose={() => setShowAlert(false)} show={showAlert} delay={3000} autohide >
                    <Toast.Header>
                        <img src="holder.js/20x20?text=%20" className="rounded me-2" alt="" />
                        <strong className="me-auto">App fingerprint type error!</strong>
                    </Toast.Header>
                    <Toast.Body>
                        Fingerprint type must be seleted before generation process.
                    </Toast.Body>
                </Toast>
            </div>
        );
    }
    return null;
};

export default HashTypeAlert;
