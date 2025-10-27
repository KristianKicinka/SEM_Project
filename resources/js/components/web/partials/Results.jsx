/**
 * @file Results.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, {useState} from "react";
import ReactDOM from "react-dom";
import { Modal, Button } from "react-bootstrap";
import CopyClipboard from "./CopyClipboard";
import Ja4xInfo from "./Ja4xInfo";
import axios from "axios";


const Results = ({ results, onClose, hashTypes, customHashTypes = [] }) => {

    const hasResults = results && results.length > 0;

    const [ja4xToShow, setJa4xToShow] = useState(null);
    const [showJa4xModal, setShowJa4xModal] = useState(false);

    // Get custom hash types that are available (they are always shown if available)
    const selectedCustomTypes = customHashTypes;

    /**
     * @brief The function ensures data item creation
     * @param {*} row Table row data
     * @param {*} index Data item index
     * @returns Table row component
     */
    const dataItem = (row, index) => {

        return (
            <tr key={index}>
                <td>{row.app_name}</td>
                <td>{row.package_name}</td>
                <td>{row.app_version}</td>
                <td>{row.sni}</td>
                <td>
                    {row.is_flagged && row.sni_flag ? (
                        <span 
                            className={`badge ${
                                row.sni_flag === 'advertisement_communication' ? 'bg-danger' :
                                row.sni_flag === 'analytics_communication' ? 'bg-info' :
                                row.sni_flag === 'cdn_communication' ? 'bg-primary' :
                                row.sni_flag === 'shared_api_communication' ? 'bg-secondary' :
                                row.sni_flag === 'blacklisted_communication' ? 'bg-dark' :
                                'bg-warning'
                            } text-white`} 
                            title={`Flagged as: ${row.sni_flag}`}
                        >
                            {row.sni_flag.replace('_communication', '')}
                        </span>
                    ) : (
                        <span className="text-muted">-</span>
                    )}
                </td>
                {(hashTypes.includes("JA3")) ? <td><CopyClipboard text={row.ja3_hash}/></td> : null}
                {(hashTypes.includes("JA3S")) ? <td><CopyClipboard text={row.ja3s_hash}/></td> : null}
                {(hashTypes.includes("JA4")) ? <td><CopyClipboard text={row.ja4_hash}/></td> : null}
                {(hashTypes.includes("JA4S")) ? <td><CopyClipboard text={row.ja4s_hash}/></td> : null}
                {(hashTypes.includes("JA4X")) ? <td>
                    <button className="btn btn-sm btn-search text-light"
                            onClick={() => handleJA4XClick(row.ja4x_hash)}>show
                    </button>
                </td> : null}
                {/* Dynamic custom hash columns */}
                {selectedCustomTypes.map((customType) => {
                    // Extract custom hash value from custom_hashes (handle both string and object)
                    const parsed = typeof row.custom_hashes === 'string' ? JSON.parse(row.custom_hashes) : row.custom_hashes;
                    
                    // Try both with and without custom_ prefix to handle different formats
                    let customHashValue = parsed?.[`custom_${customType.name}`] ?? parsed?.[customType.name] ?? 'N/A';
                
                    return (
                        <td key={`custom_${customType.name}`}>
                            <CopyClipboard text={customHashValue} />
                        </td>
                    );
                })}
            </tr>
        );
    }

    /**
     * @brief The function ensures handling JA4X show button on click event
     * @param {*} ja4x_hash_data Hash object to update
     */
    const handleJA4XClick = (ja4x_hash_data) => {
        try {
            const parsedData = JSON.parse(ja4x_hash_data);
            // Ensure parsed data is an array
            const safeData = Array.isArray(parsedData) ? parsedData : [];
            setJa4xToShow(safeData);
            setShowJa4xModal(true);
        } catch (error) {
            console.error('Error parsing JA4X data:', error);
            console.error('JA4X data:', ja4x_hash_data);
            setJa4xToShow([]);
            setShowJa4xModal(true);
        }
    }

    /**
     * @brief The function ensures colse JA4X modal box
     */
    const closeJa4xModal = () => {
        setShowJa4xModal(false);
    }

    /**
     * @brief The function ensures handling CSV export click
     */
    const handleCSVExport = () => {
        if (!hasResults) {
            alert('No data to export');
            return;
        }

        // Prepare CSV data
        const csvHeaders = [
            'App Name', 'Package Name', 'Version', 'SNI', 'Flag', 
            ...(hashTypes.includes("JA3") ? ['JA3 Hash'] : []),
            ...(hashTypes.includes("JA3S") ? ['JA3S Hash'] : []),
            ...(hashTypes.includes("JA4") ? ['JA4 Hash'] : []),
            ...(hashTypes.includes("JA4S") ? ['JA4S Hash'] : []),
            ...(hashTypes.includes("JA4X") ? ['JA4X Hash'] : []),
            ...selectedCustomTypes.map(type => type.display_name)
        ];

        const csvData = results.map(row => [
            row.app_name || '',
            row.package_name || '',
            row.app_version || '',
            row.sni || '',
            row.is_flagged && row.sni_flag ? row.sni_flag : '',
            ...(hashTypes.includes("JA3") ? [row.ja3_hash || ''] : []),
            ...(hashTypes.includes("JA3S") ? [row.ja3s_hash || ''] : []),
            ...(hashTypes.includes("JA4") ? [row.ja4_hash || ''] : []),
            ...(hashTypes.includes("JA4S") ? [row.ja4s_hash || ''] : []),
            ...(hashTypes.includes("JA4X") ? [row.ja4x_hash || ''] : []),
            ...selectedCustomTypes.map(customType => {
                const parsed = typeof row.custom_hashes === 'string' ? JSON.parse(row.custom_hashes) : row.custom_hashes;
                return parsed?.[`custom_${customType.name}`] ?? parsed?.[customType.name] ?? '';
            })
        ]);

        // Convert to CSV string
        const csvContent = [
            csvHeaders.join(','),
            ...csvData.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');

        // Get package name from first result for filename
        const packageName = results[0]?.package_name || 'unknown';
        const cleanPackageName = packageName.replace(/[^a-zA-Z0-9._-]/g, '_');
        const date = new Date().toISOString().split('T')[0];
        
        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `hash-results-${cleanPackageName}_${date}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Results component body
    return (
        <div className="Results">
            <Modal size="xl" dialogClassName="modal-95w" show={true} onHide={onClose} aria-labelledby="result-modal" scrollable={true}>
                <Modal.Header closeButton>
                    <Modal.Title id="result-modal">Results</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <table className="table table-sm table-responsive">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Package name</th>
                                <th>Version</th>
                                <th>SNI</th>
                                <th>Flag</th>
                                {(hashTypes.includes("JA3")) ? <th>JA3 hash</th> : null}
                                {(hashTypes.includes("JA3S")) ? <th>JA3S hash</th> : null}
                                {(hashTypes.includes("JA4")) ? <th>JA4 hash</th> : null}
                                {(hashTypes.includes("JA4S")) ? <th>JA4S hash</th> : null}
                                {(hashTypes.includes("JA4X")) ? <th>JA4X hash</th> : null}
                                {/* Dynamic custom hash headers */}
                                {selectedCustomTypes.map((customType) => {
                                    console.log('Creating header forss customType:', customType);
                                    return <th key={customType.id}>{customType.display_name}</th>;
                                })}
                            </tr>
                        </thead>
                    <tbody className="text-nowrap">
                        {hasResults ? results.map((row, key) => dataItem(row, key)):(
                            <tr>
                                <td colSpan={5+hashTypes.length}>No hashes found, repeat the process</td>
                            </tr>
                        )}
                    </tbody>
                </table>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={onClose}>
                        Close
                    </Button>
                    <Button className="btn-search text-light" onClick={handleCSVExport} disabled={!hasResults}>
                        <i className="fa-solid fa-file-export me-2"></i>
                        Export CSV
                    </Button>
                </Modal.Footer>
            </Modal>
            {showJa4xModal && (<Ja4xInfo data={ja4xToShow} onClose={closeJa4xModal} />)}
        </div>
    );
};

export default Results;
