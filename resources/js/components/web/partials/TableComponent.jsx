import React, { useState } from "react";
import ReactDOM from "react-dom";

import Table from "react-bootstrap/Table";
import Pagination from "./Pagination";


const TableComponent = ({columnNames, dataIndexes, data, tableName, buttons }) => {

    const [filter, setFilter] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [recordsPerPage] = useState(8);

    const indexOfLastRecord = currentPage * recordsPerPage;
    const indexOfFirstRecord = indexOfLastRecord - recordsPerPage;

    const nPages = Math.ceil(data?.length / recordsPerPage);

    let filteredData = data?.slice(indexOfFirstRecord, indexOfLastRecord);

    filteredData = filteredData?.filter((item) => {
        let result = false;
        dataIndexes?.map((index) => {
            if(item[index.toLowerCase()].toString().toLowerCase().includes(filter?.toLowerCase())){
                result = true;
            }
        });
        return result;
    });


    return (
        <div className="TableComponent pt-md-3">
            <div className="container shadow bg-white text-dark">
                <div className="row p-3">
                    <div className="col-md-4">
                        <h4 className="p-2">{tableName}</h4>
                    </div>
                    <div className="col"/>
                    <div className="col-md-5">
                        <button 
                            className="btn btn-search text-white float-end d-inline mx-3"
                            onClick={() => buttons.get('createButton')()} 
                        >New item</button>
                        <input 
                            type="text" 
                            className="form-control d-inline w-auto float-end" 
                            placeholder="search" 
                            onChange={e=>setFilter(e.target.value)}/>
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
                                                {buttons.has('infoButton') ? 
                                                <small className="btn btn-link text-primary">
                                                    <i className="fa-solid fa-circle-info"/>
                                                </small>
                                                :null}
                                                {buttons.has('updateButton') ? 
                                                <small 
                                                    className="btn btn-link text-success"
                                                    onClick={() => buttons.get('updateButton')(item)}
                                                    >
                                                    <i className="fa-solid fa-pen-to-square"/>
                                                </small>
                                                :null}
                                                {buttons.has('deleteButton') ? 
                                                <small 
                                                    className="btn btn-link text-danger"
                                                    onClick={() => buttons.get('deleteButton')(item)}
                                                >
                                                    <i className="fa-solid fa-trash"/>
                                                </small>
                                                :null}
                                            </td>
                                        </tr>
                                        );
                                    })
                                }
                            </tbody>
                        </Table>
                    </div>
                </div>
                <div className="row px-3">
                    <div className="col"></div>
                    <div className="col"></div>
                    <div className="col">
                        {data && <Pagination nPages = { nPages } currentPage = { currentPage } setCurrentPage = { setCurrentPage } /> }
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TableComponent;