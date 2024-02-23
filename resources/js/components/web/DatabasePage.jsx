import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "./partials/Navbar";

import Table from "react-bootstrap/Table";
import Form from "react-bootstrap/Form";
import InputGroup from "react-bootstrap/InputGroup";
import Button from "react-bootstrap/Button";
import Pagination from "./partials/Pagination";

import CopyClipboard from "./partials/CopyClipboard";
import http from "../../http";

import { PaginationControl } from 'react-bootstrap-pagination-control';

const DatabasePage = () => {

    const [data, setData] = useState([]);
    const [filter, setFilter] = useState('');
    const columns = ['id','name','package_name','version','created_at','hash_type','hash'];

    const [currentPage, setCurrentPage] = useState(1);
    const [recordsPerPage] = useState(8);

    const indexOfLastRecord = currentPage * recordsPerPage;
    const indexOfFirstRecord = indexOfLastRecord - recordsPerPage;

    const nPages = Math.ceil(data.length / recordsPerPage);

    let filteredData = data.slice(indexOfFirstRecord, indexOfLastRecord);
   
    filteredData = filteredData.filter((item) => {
        let result = false;
        columns.map((col) => {
            if(item[col].toString().toLowerCase().includes(filter.toLowerCase())){
                result = true;
            }
        });
        return result;
    });

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
            <div className="container pt-5">
                <div className="row">
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
                            <div className="row px-4 py-2">
                                <Table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>App name</th>
                                            <th>Package name</th>
                                            <th>Version</th>
                                            <th>Created at</th>
                                            <th>Hash type</th>
                                            <th>Hash</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredData.map((item, key) => {
                                            return (
                                                <tr key={key}>
                                                    <td>{item.id}</td>
                                                    <td>{item.name}</td>
                                                    <td>{item.package_name}</td>
                                                    <td>{item.version}</td>
                                                    <td>{item.created_at}</td>
                                                    <td>{item.hash_type}</td>
                                                    <td>
                                                        <CopyClipboard
                                                            text={item.hash}
                                                        />
                                                    </td>
                                                    
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
