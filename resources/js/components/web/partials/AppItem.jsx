import React from 'react';
import ReactDOM from 'react-dom';

const AppItem = ({item}) => {
    return (
        <div className='card'>
            <div className="card-body text-dark">
                <div className="container">
                    <div className="row gx-2">
                        <div className="col-sm-4">
                            <img src={item.thumbnail} alt="AppIcon" />
                        </div>
                        <div className="col-sm-8">
                            <h6 className="card-title">{item.title}</h6>
                            <span className='card-text'>{item.product_id}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default AppItem;