/**
 * @file DatabasePage.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "./partials/Navbar";
import Table from "react-bootstrap/Table";
import Form from "react-bootstrap/Form";
import InputGroup from "react-bootstrap/InputGroup";
import Button from "react-bootstrap/Button";

import http from "../../http";
import { PaginationControl } from 'react-bootstrap-pagination-control';
import Results from "./partials/Results";
import Ja4xInfo from "./partials/Ja4xInfo";


const DatabasePage = () => {

    const [data, setData] = useState([]);
    const [filter, setFilter] = useState('');

    const [ja4xToShow, setJa4xToShow] = useState(null);
    const [showJa4xModal, setShowJa4xModal] = useState(false);

    // Table columns
    const columns = [
        'id','name','package_name','version','ja3_hash', 'sni', 'ja3s_hash',
        'ja4_hash', 'ja4s_hash', 'ja4x_hash', 'is_dangerous', 'is_malware',
        'ip_src', 'port_src', 'ip_dest', 'port_dest', 'created_at'
    ];

    const [currentPage, setCurrentPage] = useState(1);
    const [recordsPerPage] = useState(8);

    let filteredData = data.filter(item => {
        let result = false;

        columns.map((col) => {
            if(item[col]?.toString().toLowerCase().includes(filter.toLowerCase())){
                result = true;
            }
        });
        return result;
    });

    const indexOfLastRecord = currentPage * recordsPerPage;
    const indexOfFirstRecord = indexOfLastRecord - recordsPerPage;
    const nPages = Math.ceil(filteredData.length / recordsPerPage);

    filteredData = filteredData.slice(indexOfFirstRecord, indexOfLastRecord);

    const hasFilteredData = filteredData && filteredData.length > 0;

    /**
     * @brief The function ensures handling JA4X show button on click event
     * @param {*} ja4x_hash_data Hash object to update
     */
    const handleJA4XClick = (ja4x_hash_data) => {
        setJa4xToShow(JSON.parse(ja4x_hash_data));
        setShowJa4xModal(true);
    }

    /**
     * @brief The function ensures handling search button event
     * @param {*} filter Search filter
     */
    const handleSearch = (filter) => {
        setFilter(filter);
        setCurrentPage(1);
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
    const handleCSVexport = async () => {
        try {
            // Make POST request to export CSV
            const response = await axios.post('/export-to-csv', {}, { responseType: 'blob' });

            // Create a Blob from the response data
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');

            // Set the link href to the Blob URL
            link.href = url;

            // Set the filename for the download
            link.setAttribute('download', 'hashapp-database.csv');

            // Append the link to the body and trigger the download
            document.body.appendChild(link);
            link.click();

            // Remove the link after triggering the download
            link.remove();
        } catch (error) {
            toast.error('There was an error exporting the CSV!');
            console.error('There was an error exporting the CSV:', error);
        }
    };

    /**
     * @brief The function ensures getting data from database
     */
    const getData = async () => {
        try {
            let response = await http.post('/get-app-data');
            setData(response.data);
        } catch (error) {
            toast.error('Get applications data failed!');
            console.log(`ERROR: ${error}`);
        }
    };

    useEffect(() => {
        getData();
    }, []);

    return (
        <div className="DatabasePage bg-primary bg-gradient pt-5 min-vh-100">
            <Navbar />
            <div className="container-fluid pt-5">
                <div className="row px-4">
                    <div className="card bg-white text-dark p-3">
                        <div className="card-body">
                            <div className="row p-3">
                                <div className="col">
                                    <h3 className="card-title">
                                        Fingerprint database
                                    </h3>
                                </div>
                                <div className="col"></div>
                                <div className="col">
                                    <div className="row">
                                        <div className="col">
                                            <InputGroup className="mb-3">
                                            <Form.Control
                                                placeholder="Search"
                                                aria-label="Search"
                                                aria-describedby="search_btn"
                                                onChange={e=>handleSearch(e.target.value)}
                                            />
                                            <Button
                                                id="search_btn"
                                                type="submit"
                                                className="btn-search text-light"
                                            ><i className="fa-solid fa-magnifying-glass"></i>
                                                </Button>
                                            </InputGroup>
                                        </div>
                                        <div className="col-4">
                                            <Button className="btn-search text-light" onClick={handleCSVexport}>
                                                <small className="px-1">CSV export</small>
                                                <i className="fa-solid fa-file-export"></i>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="row px-1 py-2 table-responsive">
                                <Table className="table table-sm">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>App name</th>
                                        <th>Package name</th>
                                        <th>Version</th>
                                        <th>SNI</th>
                                        <th>JA3 hash</th>
                                        <th>JA3S hash</th>
                                        <th>JA4 hash</th>
                                        <th>JA4S hash</th>
                                        <th>JA4X hashes</th>
                                        <th>Is dangerous</th>
                                        <th>Is malware</th>
                                        <th>IP src</th>
                                        <th>Port src</th>
                                        <th>IP dest</th>
                                        <th>Port dest</th>
                                        <th>Created at</th>
                                    </tr>
                                    </thead>
                                    <tbody className="text-nowrap">
                                        {hasFilteredData ? filteredData.map((item, key) => {
                                            return (
                                                <tr key={key}>
                                                    <td>{item?.id}</td>
                                                    <td>{item?.name}</td>
                                                    <td>{item?.package_name}</td>
                                                    <td>{item?.version}</td>
                                                    <td>{item?.sni}</td>
                                                    <td><b>{item?.ja3_hash}</b></td>
                                                    <td><b>{item?.ja3s_hash}</b></td>
                                                    <td><b>{item?.ja4_hash}</b></td>
                                                    <td><b>{item?.ja4s_hash}</b></td>
                                                    <td>
                                                        <button className="btn btn-sm btn-search-outline"
                                                                onClick={() => handleJA4XClick(item?.ja4x_hash)}>show
                                                        </button>
                                                    </td>
                                                    <td><b>{item?.is_dangerous}</b></td>
                                                    <td><b>{item?.is_malware}</b></td>
                                                    <td><b>{item?.ip_src}</b></td>
                                                    <td><b>{item?.port_src}</b></td>
                                                    <td><b>{item?.ip_dest}</b></td>
                                                    <td><b>{item?.port_dest}</b></td>
                                                    <td>{item?.created_at}</td>
                                                </tr>
                                            );
                                        }) : (
                                            <tr>
                                                <td colSpan={17}>No data loaded, try to refresh page.</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </Table>
                            </div>
                            <div className="row">
                                <div className="col"></div>
                                <div className="col"></div>
                                <div className="col">
                                {data && <PaginationControl page={currentPage} between={4} total={nPages} limit={1} changePage={(page) => {setCurrentPage(page)}} ellipsis={1} /> }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {showJa4xModal && (<Ja4xInfo data={ja4xToShow} onClose={closeJa4xModal} />)}
        </div>
    );
};

export default DatabasePage;
