/**
 * @file Pagination.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { Component } from 'react'


const Pagination = ({nPages, currentPage, setCurrentPage}) => {

    const pageNumbers = [...Array(nPages + 1).keys()].slice(1);

    /**
     * @brief The function ensures redirecting to next page 
     */
    const nextPage = () => {
        if(currentPage !== nPages) 
            setCurrentPage(currentPage + 1)
    }

    /**
     * @brief The function ensures redirecting to previous page 
     */
    const prevPage = () => {
        if(currentPage !== 1) 
            setCurrentPage(currentPage - 1)
    }

    // Component body
    return (
        <div className='Pagination'>
            <nav className='float-end me-4'>
                <ul className="pagination">
                    <li className="page-item">
                        <a className="page-link" onClick={prevPage} aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    {pageNumbers.map(pgNumber => (
                        <li key={pgNumber} className={`page-item ${currentPage == pgNumber ? 'active' : ''}`}>
                            <a onClick={() => setCurrentPage(pgNumber)} className='page-link'>{pgNumber}</a>
                        </li>
                    ))}

                    <li className="page-item">
                        <a className="page-link" onClick={nextPage} aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    )
};

export default Pagination;