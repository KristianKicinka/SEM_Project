/**
 * @file CustomHashesInfo.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState } from 'react';
import Modal from 'react-bootstrap/Modal';
import CopyClipboard from './CopyClipboard';

const CustomHashesInfo = ({ customHashes, onClose }) => {
    const [showModal, setShowModal] = useState(true);

    const closeModal = () => {
        setShowModal(false);
        onClose();
    };

    const hasData = customHashes && Object.keys(customHashes).length > 0;

    const dataItem = (hashName, hashValue, key) => {
        return (
            <tr key={key}>
                <td>{hashName}</td>
                <td><CopyClipboard text={hashValue || 'N/A'} /></td>
            </tr>
        );
    };

    return (
        <div className="CustomHashesInfo">
            <Modal size="lg" show={showModal} onHide={closeModal} aria-labelledby="custom-hashes-modal" scrollable={true}>
                <Modal.Header closeButton>
                    <Modal.Title id="custom-hashes-modal">Custom Hashes Information</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <table className="table table-sm table-responsive">
                        <thead>
                            <tr>
                                <th>Hash Type</th>
                                <th>Hash Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            {hasData ? Object.entries(customHashes).map(([hashName, hashValue], key) => 
                                dataItem(hashName, hashValue, key)
                            ) : (
                                <tr>
                                    <td colSpan={2}>No custom hashes found</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </Modal.Body>
                <Modal.Footer>
                    <button className="btn btn-warning bg-orange text-white" onClick={closeModal}>
                        Close
                    </button>
                </Modal.Footer>
            </Modal>
        </div>
    );
};

export default CustomHashesInfo;

