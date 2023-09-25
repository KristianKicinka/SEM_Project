import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import InputGroup from 'react-bootstrap/InputGroup';
import Button from 'react-bootstrap/Button';

import axios from 'axios';

import ImportSection from './ImportSection';
import ContentBox from './ContentBox';
import HashTypePicker from './HashTypePicker';

import StaticData from '../../../../../scripts/StaticData.json';


const SearchBox = ({
    handleShowLoading, handleCloseLoading, handleShowResults, 
    setResults, hashTypes, setHashTypes, handleShowAlert, setLoadingData
}) => {

    const [appName, setAppName] = useState();
    const [appItems, setAppItems] = useState();
    const [appItemsLoaded, setAppItemsLoaded] = useState(false);

    const get_app_items = async (event) => {
        event.preventDefault();
        appName.toLowerCase();

        try {
            let response = await axios.post('/search-app', { app_name: appName});
            setAppItems(response.data);
            setAppItemsLoaded(true);
        } catch (error) {
            console.log(error);
        }
    }

    const first_load_apps = async () => {
       setAppItems(StaticData.MainPageAppsData);
       setAppItemsLoaded(true);
    }

    useEffect(() => {
        first_load_apps();
    }, []);

    return (
        <div>
            <header className='bg-primary bg-gradient text-white pb-0'>
                <div className='container text-center py-2 pb-4'>
                    <HashTypePicker hashTypes={hashTypes} setHashTypes={setHashTypes} />
                    <ImportSection  
                        handleShowLoading={handleShowLoading} 
                        handleCloseLoading={handleCloseLoading}
                        handleShowResults={handleShowResults}
                        handleShowAlert={handleShowAlert}
                        setResults={setResults}
                        hashTypes={hashTypes}
                        setLoadingData={setLoadingData}
                    />
                </div>
                <div className='container px-4 text-center pt-5'>
                    <h1 className='fw-bolder'>Enter name of the application</h1>
                    <div className='container py-4'>
                        <div className="row">
                            <div className="col-sm-0 col-md-3"></div>
                            <div className="col-sm-12 col-md-6">
                                <Form noValidate onSubmit={get_app_items}>
                                    <InputGroup className='mb-3'>
                                        <Form.Control placeholder='Application name' aria-label='Application name' aria-describedby='search_btn' onChange={e=>setAppName(e.target.value)} />
                                        <Button id="search_btn" type='submit'  className='btn-search text-light'><i className='fa-solid fa-magnifying-glass'></i></Button>
                                    </InputGroup>
                                </Form>
                            </div>
                            <div className="col-sm-0 col-md-3"></div>
                        </div>
                    </div>
                </div>
            </header>
            {appItemsLoaded ? <ContentBox 
                                items={appItems} 
                                handleCloseLoading={handleCloseLoading}
                                handleShowLoading={handleShowLoading}
                                handleShowResults={handleShowResults}
                                handleShowAlert={handleShowAlert}
                                setResults={setResults}
                                hashTypes={hashTypes}
                                setLoadingData={setLoadingData}
                                /> : null}
        </div>
    );
}

export default SearchBox;