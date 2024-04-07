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


const DatabasePage = () => {

    const [data, setData] = useState([]);
    const [filter, setFilter] = useState('');

    // Table columns
    const columns = [
        'id','name','package_name','version','ja3_hash', 'sni', 'ja3s_hash',
        'ja4_hash', 'ja4s_hash', 'ja4x_hash', 'created_at'
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
                                    <InputGroup className="mb-3">
                                        <Form.Control
                                            placeholder="Search"
                                            aria-label="Search"
                                            aria-describedby="search_btn"
                                            onChange={e=>setFilter(e.target.value)}
                                        />
                                        <Button
                                            id="search_btn"
                                            type="submit"
                                            className="btn-search text-light"
                                        >
                                            <i className="fa-solid fa-magnifying-glass"></i>
                                        </Button>
                                    </InputGroup>
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
                                            <th>Created at</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredData.map((item, key) => {
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
                                                    <td><b>{item?.ja4x_hash}</b></td>
                                                    <td>{item?.created_at}</td>
                                                </tr>
                                            );
                                        })}
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
        </div>
    );
};

export default DatabasePage;
