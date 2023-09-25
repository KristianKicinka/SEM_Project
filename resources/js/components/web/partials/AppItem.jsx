import React, { useState, useEffect } from 'react';
import ReactDOM from "react-dom";
import axios from "axios";

import { setNewActiveProcess } from '../../../processManagement';

const AppItem = (
    { item, handleShowLoading, handleCloseLoading, handleShowResults, 
        setResults, hashTypes, handleShowAlert, setLoadingData}) => {

    const [pollingInterval, setPollingInterval] = useState(null);
    let process_id = null;

    const createHash = async (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            console.log("Select hash type");
            handleShowAlert();
            return;
        }

        handleShowLoading();
        process_id = setNewActiveProcess();
        console.log(`after set process id ${process_id}`);

        let data = {
            'package_name': item.package_name,
            'hash_types': hashTypes,
            'frontend_id': process_id,
        }

        console.log(`Data object: ${data.package_name}`);

        try {
            let results = await axios.post('/api/create-hash-appname', data);
            console.log(results.data);
            pollStatus();
            setPollingInterval(setInterval(pollStatus, 2000));
        } catch (error) {
            console.log(`ERROR: ${error}`);
        }
    }

    const handleResults = async () => {
        clearInterval(pollingInterval);
        setPollingInterval(null);

        try {
            let results = await axios.post('/api/get-process-results', {'frontend_id': process_id});
            console.log(results.data);
            setResults(results.data);
            handleCloseLoading();
            handleShowResults();
        } catch (error) {
            console.log(`ERROR: ${error}`);
        }
    }

    const pollStatus = async () => {
        let info = await getProcessInfo(process_id);
        console.log(info.status);
        setLoadingData(info);

        if(info.status === 'finished')
            handleResults();
    }

    const getProcessInfo = async (processID) => {
        try {
            let results = await axios.post('/api/get-process-info', {'frontend_id': processID});
            console.log(results.data);
            return results.data;
        } catch (error) {
            console.log(`ERROR: ${error}`);
        }
    }

    useEffect(() => {
        return () => clearInterval(pollingInterval);
    }, [pollingInterval]);

    return (
        <a href={item.package_name} onClick={createHash} className="text-decoration-none" >
            <div className="card">
                <div className="card-body text-dark">
                    <div className="container">
                        <div className="row gx-2">
                            <div className="col-sm-4">
                                <img className="w-100" src={item.thumbnail} alt="AppIcon" />
                            </div>
                            <div className="col-sm-8">
                                <h6 className="card-title">{item.app_name}</h6>
                                <span className="card-text">
                                    {item.package_name}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    );
};

export default AppItem;
