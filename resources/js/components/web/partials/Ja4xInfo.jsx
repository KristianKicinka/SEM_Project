/**
 * @file Ja4xInfo.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React from "react";
import ReactDOM from "react-dom";
import { Modal, Button } from "react-bootstrap";
import CopyClipboard from "./CopyClipboard";


const Ja4xInfo = ({ data, onClose }) => {

    const hasData = data && data.length > 0;

    console.log(data);

    /**
     * @brief The function ensures data item creation
     * @param {*} row Table row data
     * @param {*} index Data item index
     * @returns Table row component
     */
    const dataItem = (row, index) => {

        return (
            <tr key={index}>
                <td><CopyClipboard text={row.ja4x}/></td>
                <td><CopyClipboard text={row.issuer}/></td>
                <td><CopyClipboard text={row.subject}/></td>
            </tr>
        );
    }

    // Results component body
    return (
        <div className="Ja4xInfo">
            <Modal size="xl" dialogClassName="modal-80w" show={true} onHide={onClose} aria-labelledby="ja4x-modal" scrollable={true}>
                <Modal.Header closeButton>
                    <Modal.Title id="ja4x-modal">JA4X hashes data</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <table className="table table-sm table-responsive">
                        <thead>
                        <tr>
                            <th>JA4X hash</th>
                            <th>JA4X issuer string</th>
                            <th>JA4X subject string</th>
                        </tr>
                        </thead>
                        <tbody>
                        {hasData ? data.map((row,key) => dataItem(row,key)):(
                            <tr>
                                <td colSpan={3}>No JA4X hashes found</td>
                            </tr>
                        )}
                        </tbody>
                    </table>
                </Modal.Body>
            </Modal>
        </div>
    );
};

export default Ja4xInfo;
