import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "./partials/Navbar";

import Table from "react-bootstrap/Table";
import Form from "react-bootstrap/Form";
import InputGroup from "react-bootstrap/InputGroup";
import Button from "react-bootstrap/Button";
import Pagination from "react-bootstrap/Pagination";

import CopyClipboard from "./partials/CopyClipboard";

const DatabasePage = () => {

    const [data, setData] = useState([]);

    let paginationItemActive = 1;
    let paginationItems = [];

    for (let number = 1; number <= 5; number++) {
        paginationItems.push(
            <Pagination.Item key={number} active={number === paginationItemActive}>
                {number}
            </Pagination.Item>
        );
    }

    const getData = () => {
        axios.post('/getDatabaseData')
          .then((response) => {
            console.log(response.data);
            setData(response.data);
          }, (error) => {
            console.log(error);
          });
    }

    useEffect(() => {
        getData();
    }, []);

    return (
        <div className="DatabasePage bg-primary bg-gradient pt-5 vh-100">
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
                                            onChange={(e) =>
                                                setAppName(e.target.value)
                                            }
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
                                        {data.map((item, key) => {
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
                                    <Pagination>
                                        <Pagination.Prev />
                                        {paginationItems}
                                        <Pagination.Next />
                                    </Pagination>
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
