/**
 * @file TableComponent.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState } from "react";
import ReactDOM from "react-dom";

import Table from "react-bootstrap/Table";
import { PaginationControl } from 'react-bootstrap-pagination-control';


const TableComponent = ({columnNames, dataIndexes, data, tableName, buttons }) => {

    const [filter, setFilter] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [recordsPerPage] = useState(8);
   
    // Search box data filtration
    let filteredData = data.filter(item => {
        let result = false;
        dataIndexes.map((col) => {
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

    // Component body
    return (
        <div className="TableComponent pt-md-3">
            <div className="container-fluid px-2 shadow bg-white text-dark">
                <div className="row p-3">
                    <div className="col-md-4">
                        <h4 className="p-2">{tableName}</h4>
                    </div>
                    <div className="col"/>
                    <div className="col-md-5">
                        {buttons.has("createButton") ? (<button 
                            className="btn btn-search text-white float-end d-inline mx-3"
                            onClick={() => buttons.get('createButton').funct_call()} 
                        >{buttons.get('createButton').name}</button>): null}

                        <div className="input-group flex-nowrap w-50 float-end">
                            <input 
                            type="text" 
                            className="form-control d-inline float-end" 
                            placeholder="search" 
                            onChange={e=>setFilter(e.target.value)}/>
                            <span className="input-group-text bg-orange text-white">
                                <i className="fa-solid fa-magnifying-glass"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div className="row px-3">
                    <div className="col-md-12 table-responsive">
                        <Table className="table table-sm">
                            <thead>
                                <tr className="table-dark">
                                    {columnNames.map((name, key) => { return (<th key={key} >{name}</th>)})}
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody className="align-baseline">
                                {filteredData?.map((item, key) => {
                                    return (
                                        <tr key={key}>
                                            {dataIndexes?.map((name, key) =>{
                                                return (<td key={key} >{item[name.toLowerCase()]}</td>)
                                            })}
                                            <td>
                                                <div className="btn-group">
                                                    {buttons.has('infoButton') ? 
                                                    <button className="btn btn-link text-primary">
                                                        <i className="fa-solid fa-circle-info"/>
                                                    </button>
                                                    :null}
                                                    {buttons.has('updateButton') ? 
                                                    <button 
                                                        className="btn btn-link text-success"
                                                        onClick={() => buttons.get('updateButton')(item)}>
                                                        <i className="fa-solid fa-pen-to-square"/>
                                                    </button>
                                                    :null}
                                                    {buttons.has('deleteButton') ? 
                                                    <button 
                                                        className="btn btn-link text-danger"
                                                        onClick={() => buttons.get('deleteButton')(item)}>
                                                        <i className="fa-solid fa-trash"/>
                                                    </button>
                                                    :null}
                                                </div>
                                            </td>
                                        </tr>
                                        );
                                    })
                                }
                            </tbody>
                        </Table>
                    </div>
                </div>
                <div className="row px-2">
                    <div className="col"></div>
                    <div className="col"></div>
                    <div className="col">
                        {data && <PaginationControl page={currentPage} between={4} total={nPages} limit={1} changePage={(page) => {setCurrentPage(page)}} ellipsis={1} /> }
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TableComponent;