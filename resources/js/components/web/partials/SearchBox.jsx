import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import InputGroup from 'react-bootstrap/InputGroup';
import Button from 'react-bootstrap/Button';

import axios from 'axios';

import ImportSection from './ImportSection';
import ContentBox from './ContentBox';
import HashTypePicker from './HashTypePicker';


const SearchBox = ({handleShowLoading, handleCloseLoading, handleShowResults, setResults, hashTypes, setHashTypes}) => {

    const [appName, setAppName] = useState();
    const [appItems, setAppItems] = useState();
    const [appItemsLoaded, setAppItemsLoaded] = useState(false);

    const get_app_items = (event) =>{
        event.preventDefault();
        appName.toLowerCase();

        axios.post('/search', {
            app_name : appName,
          })
          .then((response) => {
            setAppItems(response.data.organic_results[0].items);
            setAppItemsLoaded(true);
            console.log(response.data.organic_results[0].items);
          }, (error) => {
            console.log(error);
          });
    
    }

    return (
        <div>
            <header className='bg-primary bg-gradient text-white pb-0'>
                <div className='container text-center py-2 pb-4'>
                    <HashTypePicker hashTypes={hashTypes} setHashTypes={setHashTypes} />
                    <ImportSection  
                        handleShowLoading={handleShowLoading} 
                        handleCloseLoading={handleCloseLoading}
                        handleShowResults={handleShowResults}
                        setResults={setResults}
                        hashTypes={hashTypes}
                    />
                </div>
                <div className='container px-4 text-center pt-5'>
                    <h1 className='fw-bolder'>Enter the name of application</h1>
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
                                setResults={setResults}
                                hashTypes={hashTypes}
                                /> : null}
        </div>
    );
}

export default SearchBox;