/**
 * @file PageHeader.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React from 'react';
import { Row, Col } from 'react-bootstrap';

const PageHeader = ({ title, description, children }) => {
    return (
        <Row className="my-3 mx-3">
            <Col className=''>
                <h2 className="text-dark mb-2">{title}</h2>
                {description && (
                    <p className="text-muted mb-0">{description}</p>
                )}
            </Col>
            {children && (
                <Col xs="auto" className="d-flex align-items-start">
                    {children}
                </Col>
            )}
        </Row>
    );
};

export default PageHeader;
