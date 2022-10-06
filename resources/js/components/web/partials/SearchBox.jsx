import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import { Link } from 'react-router-dom';

import Form from 'react-bootstrap/Form';
import InputGroup from 'react-bootstrap/InputGroup';
import Button from 'react-bootstrap/Button';
import axios from 'axios';
import { lowerCase } from 'lodash';
import ImportSection from './ImportSection';

const SearchBox = () => {

    const [appName, setAppName] = useState();

    const get_package_name = (event) =>{
        event.preventDefault();

        axios.post('/search', {
            name : appName,
          })
          .then((response) => {
            console.log(response.data);
          }, (error) => {
            console.log(error);
          });

       /* fetch(`https://play.google.com/store/search?q=${name}&c=apps`, {
            method: 'POST',
        }).then(res => res.text())
          .then(res => console.log(res))
          .catch(err => console.error(err)); */
        

    }

    return (
        <header className='bg-primary bg-gradient text-white'>
            <div className='container px-4 text-center'>
                <h1 className='fw-bolder'>Enter the name of application</h1>
                <div className='container py-4'>
                    <div className="row">
                        <div className="col-sm-0 col-md-3"></div>
                        <div className="col-sm-12 col-md-6">
                            <Form noValidate onSubmit={get_package_name}>
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
            <div className='container text-center py-4'>
                <ImportSection/>
            </div>
        </header>
    );
}

export default SearchBox;