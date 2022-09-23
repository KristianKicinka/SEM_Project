import React from 'react';
import ReactDOM from 'react-dom';

import { Link } from 'react-router-dom';

const Navbar = () => {
    return (
        <div className='navbar navbar-expand-lg navbar-dark bg-dark fixed-top'>
            <div className='container px-4'>
                <Link className='navbar-brand ps-3' to='#'>Hash App Master</Link>
            </div>
        </div>
    );
}

export default Navbar;