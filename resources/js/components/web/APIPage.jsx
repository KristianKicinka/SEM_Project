/**
 * @file APIPage.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from "react";

import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import { vs, vscDarkPlus } from 'react-syntax-highlighter/dist/esm/styles/prism';

import { Accordion } from "react-bootstrap";

import Navbar from "./partials/Navbar";
import ApiRequestsData from "../../../../scripts/ApiRequests.json";

/**
 * @brief The function ensures creating api request component
 * @param {*} param0 Data object with id, title, code, output and description
 * @returns React component body
 */
const ApiRequest = ({ id, title, pythonCode, expectedOutput, description }) => {
  return (
    <Accordion.Item eventKey={String(id)}>
      <Accordion.Header>{title}</Accordion.Header>
      <Accordion.Body>
        <h4>Description:</h4>
        <p>{description}</p>
        <div className="row">
          <div className="col">
            <h4>Python example code:</h4>
            <SyntaxHighlighter language="python" style={vscDarkPlus} showLineNumbers>
              {typeof pythonCode === "string" ? pythonCode : String(pythonCode)}
            </SyntaxHighlighter>
          </div>
          <div className="col">
            <h4>Example output:</h4>
            <SyntaxHighlighter language="json" style={vscDarkPlus}>
              {typeof expectedOutput === "string"
                ? expectedOutput
                : JSON.stringify(expectedOutput, null, 2)}
            </SyntaxHighlighter>
          </div>
        </div>
      </Accordion.Body>
    </Accordion.Item>
  );
};

const APIPage = () => {
  const [apiRequests, setApiRequests] = useState([]);

  useEffect(() => {
    console.log("ApiRequestsData:", ApiRequestsData);
    if (Array.isArray(ApiRequestsData)) {
      setApiRequests(ApiRequestsData);
    } else {
      console.error("ApiRequestsData is not an array:", ApiRequestsData);
    }
  }, []);

  // Component body
  return (
    <div className="APIPage bg-primary bg-gradient pt-5 min-vh-100">
      <Navbar />
      <div className="container pt-5">
        <div className="row">
          <div className="card bg-white text-dark p-3">
            <div className="card-body">
              <h3 className="card-title">API overview</h3>
              <div className="row">
                <p className="card-text">
                  The API was designed and implemented to provide an interface
                  for users that would allow the system to interact with other
                  applications or scripts. This is mainly the ability to access
                  the application interface through a terminal or by using a
                  script (created in e.g. Python). Examples of calls to the
                  generated interface will be described directly for each type
                  of API function in the sections below. The API can be accessed
                  by calling predefined HTTP requests. The responses of the
                  created interface are available in JSON format. Each time any
                  API function is triggered, a new request record is created in
                  the database system. The type of request, the identifier of
                  the user who made the request, and the IP address from which
                  the request came are recorded.
                </p>
              </div>
              <div className="row py-4">
                <Accordion alwaysOpen>
                  {apiRequests && apiRequests.length > 0 ? (
                    apiRequests.map((apiRequest, index) => (
                      <ApiRequest key={index} id={index} {...apiRequest} />
                    ))
                  ) : (
                    <div>Loading API requests...</div>
                  )}
                </Accordion>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default APIPage;

