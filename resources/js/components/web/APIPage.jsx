import React, { useState } from "react";
import ReactDOM from "react-dom";

import Prism from "prismjs";
import 'prismjs/components/prism-json';
import "../../../css/prism.css";

import Navbar from "./partials/Navbar";

const APIPage = () => {

    const response = {
        "app": "com.facebook.orca",
        "types": [ "JA3", "JA3S", "FlowMon"],
        "hashes": {
            "JA3":[
                "9b02ebd3a43b62d825e1ac605b621dc8",
                "775e508adfe18df754d6c979df8a6caa",
                "33b38c8778b29bc991b5200b6141f35e"
            ]
        }
    }

    return (
        <div className="APIPage bg-primary bg-gradient pt-5 vh-100">
            <Navbar />
            <div className="container pt-5">
                <div className="row">
                    <div className="card bg-white text-dark p-3">
                        <div className="card-body">
                            <h3 className="card-title">API overview</h3>
                            <div className="row">
                                <p className="card-text">
                                    This page describes the working principle of
                                    the implemented API, which also enables
                                    interaction with the created application.
                                    Through url queries, it is possible to enter
                                    the name of the application in the form of a
                                    package name, as well as to define the types
                                    of fingerprints that need to be generated.
                                    After sending the request, the response is
                                    returned in the form of a json object.
                                </p>
                            </div>
                            <div className="row py-2">
                                <div className="col">
                                <span>Request url:</span>
                                    <pre>
                                        <code className="language-text">
                                            {`http://localhost:8000/api/create_fingerprints?types[]=JA3&types[]=JA3S&types[]=NetFlow&app=com.facebook.orca`}
                                        </code>
                                    </pre>
                                </div>
                            </div>
                            <div className="row py-2">
                                <div className="col">
                                    <span className="">Response : </span>
                                    <pre>
                                        <code className="language-json">
                                            {JSON.stringify(response,null,2)}
                                        </code>
                                    </pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default APIPage;
