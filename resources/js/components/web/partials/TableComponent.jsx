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
import CustomHashesInfo from './CustomHashesInfo';


const TableComponent = ({columnNames, dataIndexes, data, tableName, title, description, buttons }) => {

    const [filter, setFilter] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [recordsPerPage] = useState(10);
    const [showCustomHashes, setShowCustomHashes] = useState(false);
    const [selectedCustomHashes, setSelectedCustomHashes] = useState(null);

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

    /**
     * @brief The function ensures handling search button event
     * @param {*} filter Search filter
     */
    const handleSearch = (filter) => {
        setFilter(filter);
        setCurrentPage(1);
    }

    /**
     * @brief The function ensures handling custom hashes show button on click event
     * @param {*} customHashes Custom hashes object to show
     */
    const handleCustomHashesClick = (customHashes) => {
        setSelectedCustomHashes(customHashes);
        setShowCustomHashes(true);
    }

    /**
     * @brief The function ensures close custom hashes modal box
     */
    const closeCustomHashesModal = () => {
        setShowCustomHashes(false);
        setSelectedCustomHashes(null);
    }

    const indexOfLastRecord = currentPage * recordsPerPage;
    const indexOfFirstRecord = indexOfLastRecord - recordsPerPage;
    const nPages = Math.ceil(filteredData.length / recordsPerPage);

    filteredData = filteredData.slice(indexOfFirstRecord, indexOfLastRecord);

    const hasFilteredData = filteredData && filteredData.length > 0;

    // Component body
    return (
        <div className="TableComponent">
            <div className="container-fluid shadow bg-white text-dark p-3">
                {/* Page Header - Title */}
                {(title || description) && (
                    <div className="row mb-3 mx-2 mt-3">
                        <div className="col">
                            <h2 className="text-dark mb-0">{title || tableName}</h2>
                        </div>
                    </div>
                )}
                
                {/* Controls Row - Description, Search, Create Button */}
                <div className="row mb-3 mx-2">
                    <div className="col-md-4">
                        {description && (
                            <p className="text-muted mb-0">{description}</p>
                        )}
                        {!title && !description && (
                            <h4 className="p-2 mb-0">{tableName}</h4>
                        )}
                    </div>
                    <div className="col"/>
                    <div className="col-md-5">
                        {buttons.has("createButton") ? (
                            <button
                                className="btn btn-warning bg-orange text-white float-end d-inline mx-3"
                                onClick={() => buttons.get('createButton').funct_call()}
                            >
                                {buttons.get('createButton').name}
                            </button>
                        ) : null}

                        <div className="input-group flex-nowrap w-50 float-end">
                            <input
                                type="text"
                                className="form-control d-inline float-end"
                                placeholder="search"
                                onChange={e=>handleSearch(e.target.value)}
                            />
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
                                {hasFilteredData ? filteredData?.map((item, key) => {
                                    return (
                                        <tr key={key}>
                                            {dataIndexes?.map((name, key) =>{
                                                if (name === 'custom_hashes') {
                                                    const customHashes = item[name.toLowerCase()];
                                                    const hasCustomHashes = customHashes && Object.keys(customHashes).length > 0;
                                                    return (
                                                        <td key={key} className="text-nowrap">
                                                            {hasCustomHashes ? (
                                                                <button 
                                                                    className="btn btn-sm btn-outline-primary"
                                                                    onClick={() => handleCustomHashesClick(customHashes)}
                                                                >
                                                                    Show ({Object.keys(customHashes).length})
                                                                </button>
                                                            ) : (
                                                                'None'
                                                            )}
                                                        </td>
                                                    );
                                                }
                                                return (<td key={key} className="text-nowrap" >{item[name.toLowerCase()]}</td>)
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
                                                    {buttons.has('startButton') ?
                                                    <button
                                                        className="btn btn-link text-success"
                                                        onClick={() => buttons.get('startButton')(item)}>
                                                        <i className="fa-solid fa-play"/>
                                                    </button>
                                                    :null}
                                                    {buttons.has('stopButton') ?
                                                    <button
                                                        className="btn btn-link text-danger"
                                                        onClick={() => buttons.get('stopButton')(item)}>
                                                        <i className="fa-solid fa-stop"/>
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
                                        )}):(
                                            <tr>
                                                <td colSpan={columnNames.length+1}>No data found.</td>
                                            </tr>
                                        )}
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
            {showCustomHashes && (
                <CustomHashesInfo 
                    customHashes={selectedCustomHashes} 
                    onClose={closeCustomHashesModal} 
                />
            )}
        </div>
    );
};

export default TableComponent;
